import {
  ActionIcon,
  Badge,
  Box,
  Button,
  Card,
  Group,
  Loader,
  SegmentedControl,
  Stack,
  Text,
  ThemeIcon,
  Title,
  Tooltip,
} from '@mantine/core'
import {
  mdiClockOutline,
  mdiCrosshairsGps,
  mdiFullscreen,
  mdiFullscreenExit,
  mdiRefresh,
  mdiServerNetwork,
  mdiSwordCross,
} from '@mdi/js'
import { Icon } from '@mdi/react'
import cx from 'clsx'
import { FC, useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useParams } from 'react-router'
import { WithGameTab } from '@Components/WithGameTab'
import { GAME_PAGE_CONTENT_WIDTH, WithNavBar } from '@Components/WithNavbar'
import { WithRole } from '@Components/WithRole'
import { AttackCrossMatrix } from '@Components/competition/AttackCrossMatrix'
import { MatrixTicker } from '@Components/competition/MatrixTicker'
import { ServiceSlaMatrix } from '@Components/competition/ServiceSlaMatrix'
import { adRoundSecondsRemaining } from '@Utils/adState'
import { epochProgress } from '@Utils/epochProgress'
import {
  buildTeamMatrix,
  type LiveMatrixAttackEvent,
} from '@Utils/matrixModel'
import { useGame, useGameStatus } from '@Hooks/useGame'
import api, { Role } from '@Api'
import classes from '@Styles/pages/Matrix.module.css'

const Matrix: FC = () => {
  const { id } = useParams()
  const numId = parseInt(id ?? '-1')
  const { t } = useTranslation()

  const { game } = useGame(numId)
  const { now: serverNow } = useGameStatus(game)
  const nowMs = serverNow.valueOf()

  // Baseline scoreboard data
  const {
    data: scoreboard,
    isValidating: scoreboardLoading,
    mutate: refreshScoreboard,
  } = api.game.useGameAdScoreboard(
    numId,
    { refreshInterval: 10_000, revalidateOnFocus: true },
    numId > 0
  )

  // Real-time attack events & visual flashes
  const [liveAttacks, setLiveAttacks] = useState<LiveMatrixAttackEvent[]>([])
  const [flashingKeys, setFlashingKeys] = useState<Set<string>>(new Set())
  const [wsConnected, setWsConnected] = useState(false)
  const wsRef = useRef<WebSocket | null>(null)

  // View mode: 'cross' (Attacker vs Victim) | 'services' (Team vs Services)
  const [viewMode, setViewMode] = useState<string>('cross')

  // Projector / Fullscreen mode
  const [isProjector, setIsProjector] = useState(false)
  const containerRef = useRef<HTMLDivElement>(null)

  // Ingest WebSocket attacks
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
        if (payload?.kind === 'attack' && payload.teamName && payload.victimTeamName) {
          const newEv: LiveMatrixAttackEvent = {
            id: `${Date.now()}-${Math.random().toString(36).substring(2, 7)}`,
            timestamp: Date.now(),
            attackerTeam: payload.teamName,
            victimTeam: payload.victimTeamName,
            challengeTitle: payload.challengeTitle || 'Challenge',
            blood: payload.type || 'Normal',
          }

          setLiveAttacks((prev) => [newEv, ...prev.slice(0, 99)])

          // Flash target cell
          const cellKey = `${payload.teamName}:${payload.victimTeamName}`
          setFlashingKeys((prev) => new Set([...prev, cellKey]))
          setTimeout(() => {
            setFlashingKeys((prev) => {
              const next = new Set(prev)
              next.delete(cellKey)
              return next
            })
          }, 2000)
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

  // Fullscreen change handler
  useEffect(() => {
    const handleFullscreenChange = () => {
      const isFs = Boolean(document.fullscreenElement)
      setIsProjector(isFs)
    }
    document.addEventListener('fullscreenchange', handleFullscreenChange)
    return () => document.removeEventListener('fullscreenchange', handleFullscreenChange)
  }, [])

  const toggleProjector = useCallback(async () => {
    if (!isProjector) {
      try {
        if (containerRef.current?.requestFullscreen) {
          await containerRef.current.requestFullscreen()
        } else {
          setIsProjector(true)
        }
      } catch {
        setIsProjector(true)
      }
    } else {
      try {
        if (document.fullscreenElement) {
          await document.exitFullscreen()
        } else {
          setIsProjector(false)
        }
      } catch {
        setIsProjector(false)
      }
    }
  }, [isProjector])

  const teams = scoreboard?.teams ?? []
  const challenges = scoreboard?.challenges ?? []
  const latestRound = scoreboard?.latestRound ?? 0
  const tickSeconds = scoreboard?.tickSeconds ?? 60
  const epochTicks = scoreboard?.epochTicks ?? 8
  const startRound = scoreboard?.startRound ?? null

  const seconds = adRoundSecondsRemaining(
    scoreboard?.currentRoundEndsAt,
    nowMs,
    false,
    null
  )

  const epoch = latestRound > 0 ? epochProgress(latestRound, startRound, epochTicks) : null

  // Aggregated team matrix
  const matrixData = useMemo(() => buildTeamMatrix(teams, liveAttacks), [teams, liveAttacks])

  return (
    <WithNavBar width={GAME_PAGE_CONTENT_WIDTH} competition>
      <WithRole requiredRole={Role.User}>
        <WithGameTab>
          <div
            ref={containerRef}
            className={cx(classes.root, { 'projector-fullscreen': isProjector })}
            data-matrix-screen
          >
            {/* Top Stage Bar: Round Metrics, View Switcher & Projector Mode */}
            <div className={classes.stageBar}>
              <Group gap="md" wrap="wrap">
                <Group gap="xs">
                  <ThemeIcon color="red" variant="light" size="md">
                    <Icon path={mdiCrosshairsGps} size={0.8} />
                  </ThemeIcon>
                  <Title order={4}>
                    {t('game.matrix.stage_title', 'Live Attack Heatmap Matrix')}
                  </Title>
                </Group>

                <Group gap="xs">
                  <Badge color="blue" variant="filled" size="sm">
                    {latestRound > 0 ? `Round #${latestRound}` : 'Warmup'}
                  </Badge>

                  {epoch && (
                    <Badge color="grape" variant="light" size="sm">
                      Epoch {epoch.epoch} ({epoch.tick}/{epoch.totalTicks})
                    </Badge>
                  )}

                  <Badge
                    color={seconds && seconds <= 15 ? 'red' : 'gray'}
                    variant={seconds && seconds <= 15 ? 'filled' : 'light'}
                    size="sm"
                    leftSection={<Icon path={mdiClockOutline} size={0.7} />}
                  >
                    {seconds !== null ? `${seconds}s` : '—'}
                  </Badge>

                  <Badge color={wsConnected ? 'teal' : 'orange'} variant="dot" size="xs">
                    {wsConnected ? 'Live' : 'Reconnecting'}
                  </Badge>
                </Group>
              </Group>

              <Group gap="xs" wrap="wrap">
                <SegmentedControl
                  value={viewMode}
                  onChange={setViewMode}
                  size="xs"
                  data={[
                    {
                      label: (
                        <Group gap={4} wrap="nowrap">
                          <Icon path={mdiSwordCross} size={0.7} />
                          <span>Cross-Matrix (PvP)</span>
                        </Group>
                      ),
                      value: 'cross',
                    },
                    {
                      label: (
                        <Group gap={4} wrap="nowrap">
                          <Icon path={mdiServerNetwork} size={0.7} />
                          <span>Service Health (SLA)</span>
                        </Group>
                      ),
                      value: 'services',
                    },
                  ]}
                />

                <Tooltip label={isProjector ? 'Keluar Layar Penuh' : 'Mode Layar Penuh (Proyektor Panggung)'}>
                  <Button
                    variant={isProjector ? 'filled' : 'default'}
                    color={isProjector ? 'red' : undefined}
                    size="xs"
                    leftSection={
                      <Icon path={isProjector ? mdiFullscreenExit : mdiFullscreen} size={0.8} />
                    }
                    onClick={toggleProjector}
                  >
                    {isProjector ? 'Exit Stage' : 'Projector Mode'}
                  </Button>
                </Tooltip>

                <ActionIcon
                  variant="default"
                  size="sm"
                  loading={scoreboardLoading}
                  onClick={() => void refreshScoreboard()}
                  aria-label="Refresh data"
                >
                  <Icon path={mdiRefresh} size={0.8} />
                </ActionIcon>
              </Group>
            </div>

            {/* Matrix View Body */}
            {viewMode === 'cross' ? (
              <AttackCrossMatrix matrix={matrixData} flashingKeys={flashingKeys} />
            ) : (
              <ServiceSlaMatrix teams={teams} challenges={challenges} />
            )}

            {/* Bottom Real-time Attack Ticker Stream */}
            <MatrixTicker events={liveAttacks} />
          </div>
        </WithGameTab>
      </WithRole>
    </WithNavBar>
  )
}

export default Matrix
