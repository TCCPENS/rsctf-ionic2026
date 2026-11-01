import { Badge, Box, Group, Text, Tooltip } from '@mantine/core'
import { FC } from 'react'
import { useTranslation } from 'react-i18next'
import { calculateServiceHealth } from '@Utils/matrixModel'
import type { AdScoreboardChallenge, AdTeamScoreModel } from '@Api'
import classes from '@Styles/Matrix.module.css'

interface ServiceSlaMatrixProps {
  teams: AdTeamScoreModel[]
  challenges: AdScoreboardChallenge[]
}

export const ServiceSlaMatrix: FC<ServiceSlaMatrixProps> = ({ teams, challenges }) => {
  const { t } = useTranslation()

  if (!teams || teams.length === 0 || !challenges || challenges.length === 0) {
    return (
      <Box p="xl" ta="center">
        <Text c="dimmed" size="sm">
          {t('game.matrix.no_services', 'Belum ada data service/tantangan A&D.')}
        </Text>
      </Box>
    )
  }

  return (
    <div className={classes.tableWrapper}>
      <table className={classes.matrixTable} role="grid" aria-label={t('game.matrix.services_label', 'Service SLA Grid')}>
        <thead>
          <tr>
            <th className={classes.cornerHeader} scope="col">
              TEAM NAME
            </th>
            <th className={classes.colHeader} scope="col">
              DEFENSE %
            </th>
            <th className={classes.colHeader} scope="col">
              SLA %
            </th>
            {challenges.map((ch) => (
              <th key={ch.challengeId} className={classes.colHeader} scope="col" title={ch.title}>
                <Text size="xs" fw={700} truncate maw={140}>
                  {ch.title}
                </Text>
              </th>
            ))}
            <th className={classes.colHeader} scope="col">
              TOTAL SCORE
            </th>
          </tr>
        </thead>
        <tbody>
          {teams.map((team) => {
            const defPct = (team.defenseRate * 100).toFixed(1)
            const slaPct = (team.slaRate * 100).toFixed(1)

            return (
              <tr key={team.teamId}>
                <th className={classes.rowHeader} scope="row" title={team.teamName}>
                  <Group gap="xs" wrap="nowrap">
                    <Badge
                      variant={team.rank === 1 ? 'filled' : 'light'}
                      color={team.rank === 1 ? 'yellow' : 'gray'}
                      size="xs"
                    >
                      #{team.rank}
                    </Badge>
                    <Text size="xs" fw={700} truncate maw={160}>
                      {team.teamName}
                    </Text>
                  </Group>
                </th>

                <td>
                  <Text size="xs" fw={600} c={team.defenseRate >= 0.99 ? 'teal' : 'orange'}>
                    {defPct}%
                  </Text>
                </td>

                <td>
                  <Text size="xs" fw={600} c={team.slaRate >= 0.99 ? 'teal' : 'red'}>
                    {slaPct}%
                  </Text>
                </td>

                {challenges.map((ch) => {
                  const svc = team.services.find((s) => s.challengeId === ch.challengeId)
                  const health = calculateServiceHealth(svc)

                  return (
                    <td key={ch.challengeId}>
                      <Tooltip
                        label={`${team.teamName} - ${ch.title}: Status ${health.status}, Captures: ${svc?.captureCount ?? 0}`}
                        withArrow
                      >
                        <Group justify="center" gap={4} wrap="nowrap">
                          <Badge color={health.color} variant="filled" size="xs">
                            {health.label}
                          </Badge>
                          {svc && svc.captureCount > 0 && (
                            <Badge color="red" variant="light" size="xs">
                              +{svc.captureCount}
                            </Badge>
                          )}
                        </Group>
                      </Tooltip>
                    </td>
                  )
                })}

                <td className={classes.summaryCol}>
                  <Text size="sm" fw={700}>
                    {team.settledTotal.toFixed(1)}
                  </Text>
                </td>
              </tr>
            )
          })}
        </tbody>
      </table>
    </div>
  )
}
