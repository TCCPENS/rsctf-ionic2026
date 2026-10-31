import {
  ActionIcon,
  Badge,
  Card,
  Grid,
  Group,
  Loader,
  Paper,
  ScrollArea,
  SimpleGrid,
  Stack,
  Table,
  Text,
  TextInput,
  ThemeIcon,
  Title,
  Tooltip,
} from '@mantine/core'
import {
  mdiArrowLeftBold,
  mdiArrowRightBold,
  mdiCheckCircleOutline,
  mdiCrosshairsGps,
  mdiMagnify,
  mdiRefresh,
  mdiShieldAlertOutline,
  mdiSwordCross,
} from '@mdi/js'
import { Icon } from '@mdi/react'
import dayjs from 'dayjs'
import { FC, useEffect, useMemo, useRef, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useParams } from 'react-router'
import { WithGameMonitor } from '@Components/WithGameMonitor'
import { useLanguage } from '@Utils/I18n'
import api, { AdScoreboardModel, AdTeamScoreModel } from '@Api'
import misc from '@Styles/Misc.module.css'

interface LiveAttackEvent {
  id: string
  timestamp: number
  attackerTeam: string
  victimTeam: string
  challengeTitle: string
  blood: string
}

const Attacks: FC = () => {
  const { id } = useParams()
  const numId = parseInt(id ?? '-1')
  const { t } = useTranslation()
  const { locale } = useLanguage()

  // Realtime attack stream from plain websocket mirror
  const [liveAttacks, setLiveAttacks] = useState<LiveAttackEvent[]>([])
  const [wsConnected, setWsConnected] = useState(false)
  const wsRef = useRef<WebSocket | null>(null)

  // Fetch scoreboard for aggregate stats and evidence
  const {
    data: scoreboard,
    isValidating: scoreboardLoading,
    mutate: refreshScoreboard,
  } = api.game.useGameAdScoreboard(
    numId,
    { refreshInterval: 10_000, revalidateOnFocus: true },
    numId > 0
  )

  const [search, setSearch] = useState('')

  useEffect(() => {
    if (!numId || numId <= 0) return

    let isSubscribed = true
    const proto = window.location.protocol === 'https:' ? 'wss' : 'ws'
    const socket = new WebSocket(`${proto}://${window.location.host}/hub/attack/ws?game=${numId}`)
    wsRef.current = socket

    socket.onopen = () => {
      if (isSubscribed) setWsConnected(true)
    }

    socket.onmessage = (event) => {
      try {
        const payload = JSON.parse(event.data)
        if (payload?.kind === 'attack' && payload.teamName) {
          const newEv: LiveAttackEvent = {
            id: `${Date.now()}-${Math.random().toString(36).substring(2, 7)}`,
            timestamp: Date.now(),
            attackerTeam: payload.teamName,
            victimTeam: payload.victimTeamName || 'Unknown',
            challengeTitle: payload.challengeTitle || 'Challenge',
            blood: payload.type || 'Normal',
          }
          setLiveAttacks((prev) => [newEv, ...prev.slice(0, 199)])
        }
      } catch {}
    }

    socket.onclose = () => {
      if (isSubscribed) setWsConnected(false)
    }

    return () => {
      isSubscribed = false
      if (wsRef.current) {
        wsRef.current.onclose = null
        wsRef.current.close()
        wsRef.current = null
      }
    }
  }, [numId])

  const teams = scoreboard?.teams ?? []
  const challenges = scoreboard?.challenges ?? []
  const evidence = scoreboard?.evidence

  const filteredTeams = useMemo(() => {
    if (!search.trim()) return teams
    const q = search.toLowerCase().trim()
    return teams.filter((t) => t.teamName.toLowerCase().includes(q))
  }, [teams, search])

  const totalCaptures = evidence?.acceptedCaptures ?? 0
  const latestRound = scoreboard?.latestRound ?? 0

  return (
    <WithGameMonitor isLoading={!scoreboard && scoreboardLoading}>
      <Stack gap="md" w="100%">
        {/* Header Overview Cards */}
        <SimpleGrid cols={{ base: 1, sm: 2, md: 4 }} spacing="sm">
          <Card withBorder padding="sm" radius="md">
            <Group justify="space-between">
              <Text size="xs" c="dimmed" fw={600}>
                TOTAL ACCEPTED ATTACKS
              </Text>
              <ThemeIcon variant="light" color="red" size="sm">
                <Icon path={mdiSwordCross} size={0.7} />
              </ThemeIcon>
            </Group>
            <Text fw={700} size="xl" mt={4}>
              {totalCaptures}
            </Text>
            <Text size="xs" c="dimmed">
              Recorded in official epoch evidence
            </Text>
          </Card>

          <Card withBorder padding="sm" radius="md">
            <Group justify="space-between">
              <Text size="xs" c="dimmed" fw={600}>
                CURRENT ROUND / TICK
              </Text>
              <ThemeIcon variant="light" color="blue" size="sm">
                <Icon path={mdiCrosshairsGps} size={0.7} />
              </ThemeIcon>
            </Group>
            <Text fw={700} size="xl" mt={4}>
              {latestRound > 0 ? `Round ${latestRound}` : 'Warmup'}
            </Text>
            <Text size="xs" c="dimmed">
              Tick length: {scoreboard?.tickSeconds ?? 60}s
            </Text>
          </Card>

          <Card withBorder padding="sm" radius="md">
            <Group justify="space-between">
              <Text size="xs" c="dimmed" fw={600}>
                ACTIVE DEFENSE RATIO
              </Text>
              <ThemeIcon variant="light" color="teal" size="sm">
                <Icon path={mdiCheckCircleOutline} size={0.7} />
              </ThemeIcon>
            </Group>
            <Text fw={700} size="xl" mt={4}>
              {evidence?.defenseOpportunities
                ? `${((evidence.protectedOpportunities / evidence.defenseOpportunities) * 100).toFixed(1)}%`
                : '100%'}
            </Text>
            <Text size="xs" c="dimmed">
              {evidence?.protectedOpportunities ?? 0} / {evidence?.defenseOpportunities ?? 0} protected
            </Text>
          </Card>

          <Card withBorder padding="sm" radius="md">
            <Group justify="space-between">
              <Text size="xs" c="dimmed" fw={600}>
                LIVE ATTACK WEBSOCKET
              </Text>
              <Badge color={wsConnected ? 'teal' : 'orange'} variant="dot" size="xs">
                {wsConnected ? 'Streaming' : 'Connecting'}
              </Badge>
            </Group>
            <Text fw={700} size="xl" mt={4}>
              {liveAttacks.length} Recent
            </Text>
            <Text size="xs" c="dimmed">
              Real-time feed listener active
            </Text>
          </Card>
        </SimpleGrid>

        {/* Attack Matrix & Team Breakdown */}
        <Grid>
          {/* Left Column: Team Attack Performance Table */}
          <Grid.Col span={{ base: 12, md: 7 }}>
            <Paper withBorder p="md" radius="md">
              <Group justify="space-between" mb="sm" wrap="wrap">
                <Group gap="xs">
                  <Icon path={mdiSwordCross} size={1} color="var(--mantine-color-red-6)" />
                  <Title order={5}>A&D Team Attack Standing</Title>
                </Group>
                <Group gap="xs">
                  <TextInput
                    placeholder="Search team..."
                    size="xs"
                    leftSection={<Icon path={mdiMagnify} size={0.7} />}
                    value={search}
                    onChange={(e) => setSearch(e.currentTarget.value)}
                    style={{ width: 180 }}
                  />
                  <ActionIcon variant="subtle" size="sm" onClick={() => void refreshScoreboard()}>
                    <Icon path={mdiRefresh} size={0.8} />
                  </ActionIcon>
                </Group>
              </Group>

              <ScrollArea h="50vh" scrollbarSize={6}>
                <Table striped highlightOnHover verticalSpacing="xs">
                  <Table.Thead>
                    <Table.Tr>
                      <Table.Th style={{ width: 50 }}>#</Table.Th>
                      <Table.Th>Team Name</Table.Th>
                      <Table.Th style={{ textAlign: 'right' }}>Captures (Attack)</Table.Th>
                      <Table.Th style={{ textAlign: 'right' }}>Offense %</Table.Th>
                      <Table.Th style={{ textAlign: 'right' }}>Defense %</Table.Th>
                      <Table.Th style={{ textAlign: 'right' }}>SLA %</Table.Th>
                    </Table.Tr>
                  </Table.Thead>
                  <Table.Tbody>
                    {filteredTeams.length === 0 ? (
                      <Table.Tr>
                        <Table.Td colSpan={6} style={{ textAlign: 'center', padding: '2rem' }}>
                          <Text c="dimmed" size="sm">
                            No team attack data recorded yet.
                          </Text>
                        </Table.Td>
                      </Table.Tr>
                    ) : (
                      filteredTeams.map((t) => {
                        const totalCaps = t.services.reduce((acc, s) => acc + (s.captureCount || 0), 0)
                        return (
                          <Table.Tr key={t.teamId}>
                            <Table.Td>
                              <Badge
                                variant={t.rank === 1 ? 'filled' : 'light'}
                                color={t.rank === 1 ? 'yellow' : 'gray'}
                                size="sm"
                              >
                                {t.rank}
                              </Badge>
                            </Table.Td>
                            <Table.Td>
                              <Text fw={600} size="sm">
                                {t.teamName}
                              </Text>
                              {t.division && (
                                <Text size="xs" c="dimmed">
                                  {t.division}
                                </Text>
                              )}
                            </Table.Td>
                            <Table.Td style={{ textAlign: 'right' }}>
                              <Badge color={totalCaps > 0 ? 'red' : 'gray'} variant="light" size="sm">
                                {totalCaps} hits
                              </Badge>
                            </Table.Td>
                            <Table.Td style={{ textAlign: 'right' }}>
                              <Text size="sm" fw={500} className={misc.ffmono}>
                                {(t.offenseRate * 100).toFixed(1)}%
                              </Text>
                            </Table.Td>
                            <Table.Td style={{ textAlign: 'right' }}>
                              <Text
                                size="sm"
                                fw={500}
                                className={misc.ffmono}
                                c={t.defenseRate >= 0.99 ? 'teal' : 'orange'}
                              >
                                {(t.defenseRate * 100).toFixed(1)}%
                              </Text>
                            </Table.Td>
                            <Table.Td style={{ textAlign: 'right' }}>
                              <Text
                                size="sm"
                                fw={500}
                                className={misc.ffmono}
                                c={t.slaRate >= 0.99 ? 'teal' : 'red'}
                              >
                                {(t.slaRate * 100).toFixed(1)}%
                              </Text>
                            </Table.Td>
                          </Table.Tr>
                        )
                      })
                    )}
                  </Table.Tbody>
                </Table>
              </ScrollArea>
            </Paper>
          </Grid.Col>

          {/* Right Column: Live Inbound Attack Log (Realtime WebSocket) */}
          <Grid.Col span={{ base: 12, md: 5 }}>
            <Paper withBorder p="md" radius="md">
              <Group justify="space-between" mb="sm">
                <Group gap="xs">
                  <Icon path={mdiCrosshairsGps} size={1} color="var(--mantine-color-grape-6)" />
                  <Title order={5}>Live Attack Feed</Title>
                </Group>
                <Badge color={wsConnected ? 'teal' : 'gray'} size="xs" variant="light">
                  {wsConnected ? 'LIVE FEED' : 'OFFLINE'}
                </Badge>
              </Group>

              <ScrollArea h="50vh" scrollbarSize={6}>
                {liveAttacks.length === 0 ? (
                  <Stack align="center" justify="center" h="40vh" gap="xs">
                    <ThemeIcon size="xl" radius="xl" variant="light" color="gray">
                      <Icon path={mdiSwordCross} size={1.2} />
                    </ThemeIcon>
                    <Text size="sm" fw={600}>
                      Listening for incoming attacks...
                    </Text>
                    <Text size="xs" c="dimmed" ta="center" maw={260}>
                      Attacks submitted during active rounds will stream here live.
                    </Text>
                  </Stack>
                ) : (
                  <Stack gap="xs">
                    {liveAttacks.map((ev) => (
                      <Card key={ev.id} withBorder padding="xs" radius="sm">
                        <Group justify="space-between" wrap="nowrap">
                          <Group gap="xs" wrap="nowrap">
                            <Badge
                              color={ev.blood === 'FirstBlood' ? 'yellow' : 'red'}
                              variant="filled"
                              size="xs"
                            >
                              {ev.blood === 'FirstBlood' ? 'FIRST BLOOD' : 'HIT'}
                            </Badge>
                            <Text size="xs" fw={700}>
                              {ev.attackerTeam}
                            </Text>
                          </Group>
                          <Text size="xs" c="dimmed" ff="monospace">
                            {dayjs(ev.timestamp).locale(locale).format('LTS')}
                          </Text>
                        </Group>

                        <Group gap={4} mt={4}>
                          <Text size="xs" c="dimmed">
                            Attacked:
                          </Text>
                          <Text size="xs" fw={600} c="red">
                            {ev.victimTeam}
                          </Text>
                          <Text size="xs" c="dimmed">
                            on
                          </Text>
                          <Text size="xs" fw={500}>
                            {ev.challengeTitle}
                          </Text>
                        </Group>
                      </Card>
                    ))}
                  </Stack>
                )}
              </ScrollArea>
            </Paper>
          </Grid.Col>
        </Grid>
      </Stack>
    </WithGameMonitor>
  )
}

export default Attacks
