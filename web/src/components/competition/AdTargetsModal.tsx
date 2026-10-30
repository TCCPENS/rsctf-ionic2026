import {
  Badge,
  Box,
  Button,
  Card,
  CopyButton,
  Divider,
  Group,
  Loader,
  Modal,
  ModalProps,
  ScrollArea,
  SimpleGrid,
  Stack,
  Text,
  TextInput,
  ThemeIcon,
  Title,
  Tooltip,
} from '@mantine/core'
import {
  mdiCheck,
  mdiContentCopy,
  mdiCrosshairsGps,
  mdiMagnify,
  mdiRefresh,
  mdiServerNetwork,
  mdiShieldAlertOutline,
} from '@mdi/js'
import { Icon } from '@mdi/react'
import { FC, useMemo, useState } from 'react'
import { useTranslation } from 'react-i18next'
import api from '@Api'
import misc from '@Styles/Misc.module.css'

interface AdTargetsModalProps extends ModalProps {
  gameId: number
}

const statusBadge = (status?: string | null) => {
  switch (status) {
    case 'Ok':
      return { color: 'teal', label: 'VULNERABLE / UP' }
    case 'Mumble':
      return { color: 'yellow', label: 'MUMBLE' }
    case 'Offline':
      return { color: 'red', label: 'DOWN / OFFLINE' }
    default:
      return { color: 'gray', label: status || 'UNKNOWN' }
  }
}

export const AdTargetsModal: FC<AdTargetsModalProps> = ({ gameId, ...modalProps }) => {
  const { t } = useTranslation()
  const [filter, setFilter] = useState('')

  const { data: targets, error, isValidating, mutate } = api.game.useGameAdTargets(
    gameId,
    { refreshInterval: 10_000, revalidateOnFocus: true },
    Boolean(modalProps.opened && gameId > 0)
  )

  const challenges = targets?.challenges ?? []
  const currentRound = targets?.currentRound ?? 0

  const filteredChallenges = useMemo(() => {
    if (!filter.trim()) return challenges
    const q = filter.toLowerCase().trim()
    return challenges.map((ch) => ({
      ...ch,
      teams: ch.teams.filter(
        (tm) =>
          tm.teamName.toLowerCase().includes(q) ||
          (tm.ip && tm.ip.toLowerCase().includes(q)) ||
          (tm.division && tm.division.toLowerCase().includes(q))
      ),
    }))
  }, [challenges, filter])

  return (
    <Modal
      size="54rem"
      centered
      title={
        <Group gap="sm">
          <ThemeIcon variant="light" color="red" size="lg">
            <Icon path={mdiCrosshairsGps} size={1} />
          </ThemeIcon>
          <Box>
            <Title order={4}>{t('game.ad.targets_title', 'Target Lawan (Attack Targets)')}</Title>
            <Text size="xs" c="dimmed">
              {currentRound > 0
                ? t('game.ad.targets_subtitle', 'Round {{round}} — Daftar IP dan service mesin tim lawan untuk diserang', {
                    round: currentRound,
                  })
                : t('game.ad.targets_warmup', 'Warmup — Target akan muncul setelah ronde 1 dimulai')}
            </Text>
          </Box>
        </Group>
      }
      {...modalProps}
    >
      <Stack gap="md">
        <Group justify="space-between" wrap="wrap" gap="sm">
          <TextInput
            placeholder={t('game.ad.filter_target_placeholder', 'Cari nama tim atau IP...')}
            leftSection={<Icon path={mdiMagnify} size={0.8} />}
            value={filter}
            onChange={(e) => setFilter(e.currentTarget.value)}
            style={{ flex: '1 1 16rem' }}
            size="sm"
          />
          <Button
            size="sm"
            variant="light"
            color="gray"
            leftSection={<Icon path={mdiRefresh} size={0.8} />}
            loading={isValidating}
            onClick={() => void mutate()}
          >
            {t('common.button.refresh', 'Refresh')}
          </Button>
        </Group>

        {error && (
          <Card withBorder p="sm" bg="var(--mantine-color-red-light)">
            <Group gap="xs">
              <Icon path={mdiShieldAlertOutline} size={1} color="var(--mantine-color-red-6)" />
              <Text size="sm" c="red">
                {t('game.ad.targets_load_error', 'Gagal memuat daftar target lawan. Pastikan Anda sudah terdaftar di tim dan ronde sudah dimulai.')}
              </Text>
            </Group>
          </Card>
        )}

        {challenges.length === 0 && !error && (
          <Box py="xl" ta="center">
            {isValidating ? (
              <Group justify="center" gap="sm">
                <Loader size="sm" />
                <Text size="sm" c="dimmed">
                  {t('common.content.loading', 'Memuat target lawan...')}
                </Text>
              </Group>
            ) : (
              <Stack gap="xs" align="center">
                <ThemeIcon size="xl" radius="xl" variant="light" color="gray">
                  <Icon path={mdiServerNetwork} size={1.2} />
                </ThemeIcon>
                <Text size="sm" fw={600}>
                  {t('game.ad.no_targets_yet', 'Belum ada target tersedia')}
                </Text>
                <Text size="xs" c="dimmed" maw={400}>
                  {currentRound === 0
                    ? t('game.ad.warmup_wait', 'Kompetisi masih dalam fase Warmup. Target lawan akan dibuka saat Round 1 dimulai.')
                    : t('game.ad.no_targets_desc', 'Tidak ada target aktif yang terdeteksi untuk game ini.')}
                </Text>
              </Stack>
            )}
          </Box>
        )}

        <ScrollArea h="55vh" scrollbarSize={6}>
          <Stack gap="lg" pr="xs">
            {filteredChallenges.map((challenge) => (
              <Box key={challenge.challengeId}>
                <Group justify="space-between" mb="xs" wrap="nowrap">
                  <Group gap="xs">
                    <Badge color="red" variant="filled">
                      Challenge #{challenge.challengeId}
                    </Badge>
                    <Text fw={700} size="sm">
                      {challenge.title}
                    </Text>
                  </Group>
                  <Text size="xs" c="dimmed">
                    {challenge.teams.length} {t('game.ad.opponent_count', 'target')}
                  </Text>
                </Group>

                <SimpleGrid cols={{ base: 1, sm: 2 }} spacing="xs">
                  {challenge.teams.map((team) => {
                    const badge = statusBadge(team.lastCheckStatus)
                    const targetUri = team.ip && team.port ? `${team.ip}:${team.port}` : team.ip ?? '—'
                    return (
                      <Card key={team.participationId} withBorder padding="xs" radius="md">
                        <Stack gap={4}>
                          <Group justify="space-between" wrap="nowrap">
                            <Text fw={600} size="sm" truncate title={team.teamName}>
                              {team.teamName}
                            </Text>
                            <Badge color={badge.color} variant="light" size="xs">
                              {badge.label}
                            </Badge>
                          </Group>

                          <Group justify="space-between" align="center" mt={2}>
                            <Group gap={4}>
                              <Text size="xs" c="dimmed">
                                Target:
                              </Text>
                              <Text size="xs" fw={700} className={misc.ffmono}>
                                {targetUri}
                              </Text>
                            </Group>

                            <CopyButton value={targetUri}>
                              {({ copied, copy }) => (
                                <Tooltip label={copied ? t('common.content.copied', 'Tersalin!') : t('common.button.copy', 'Salin IP:Port')}>
                                  <Button
                                    size="compact-xs"
                                    variant="subtle"
                                    color={copied ? 'teal' : 'gray'}
                                    leftSection={<Icon path={copied ? mdiCheck : mdiContentCopy} size={0.7} />}
                                    onClick={copy}
                                  >
                                    {copied ? t('common.content.copied', 'Disalin') : t('common.button.copy', 'Copy')}
                                  </Button>
                                </Tooltip>
                              )}
                            </CopyButton>
                          </Group>

                          {team.division && (
                            <Text size="xs" c="dimmed">
                              Divisi: {team.division}
                            </Text>
                          )}
                        </Stack>
                      </Card>
                    )
                  })}
                </SimpleGrid>
                <Divider mt="md" />
              </Box>
            ))}
          </Stack>
        </ScrollArea>
      </Stack>
    </Modal>
  )
}
