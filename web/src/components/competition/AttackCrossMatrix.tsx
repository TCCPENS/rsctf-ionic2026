import { Badge, Box, Text, Tooltip } from '@mantine/core'
import cx from 'clsx'
import { FC, useMemo } from 'react'
import { useTranslation } from 'react-i18next'
import { TeamMatrixData } from '@Utils/matrixModel'
import classes from '@Styles/pages/Matrix.module.css'

interface AttackCrossMatrixProps {
  matrix: TeamMatrixData
  flashingKeys?: Set<string>
}

export const AttackCrossMatrix: FC<AttackCrossMatrixProps> = ({ matrix, flashingKeys }) => {
  const { t } = useTranslation()
  const { teams, cells, attackerTotals, victimTotals, totalHits } = matrix

  if (!teams || teams.length === 0) {
    return (
      <Box p="xl" ta="center">
        <Text c="dimmed" size="sm">
          {t('game.matrix.no_teams', 'Belum ada tim yang terdaftar di kompetisi ini.')}
        </Text>
      </Box>
    )
  }

  return (
    <div className={classes.tableWrapper}>
      <table className={classes.matrixTable} role="grid" aria-label={t('game.matrix.cross_label', 'Attack Cross-Matrix')}>
        <thead>
          <tr>
            <th className={classes.cornerHeader} scope="col">
              ATTACKER \ VICTIM
            </th>
            {teams.map((victim) => (
              <th key={victim.teamId} className={classes.colHeader} scope="col" title={victim.teamName}>
                <Text size="xs" fw={700} truncate maw={120}>
                  {victim.teamName}
                </Text>
              </th>
            ))}
            <th className={classes.colHeader} scope="col">
              TOTAL SCORED
            </th>
          </tr>
        </thead>
        <tbody>
          {teams.map((attacker) => {
            const attackerName = attacker.teamName
            const hitsScored = attackerTotals[attackerName] ?? 0

            return (
              <tr key={attacker.teamId}>
                <th className={classes.rowHeader} scope="row" title={attackerName}>
                  <Text size="xs" fw={700} truncate maw={160}>
                    {attackerName}
                  </Text>
                </th>

                {teams.map((victim) => {
                  const victimName = victim.teamName
                  const key = `${attackerName}:${victimName}`
                  const cell = cells[key]
                  const isSelf = attackerName === victimName
                  const hits = cell?.hits ?? 0
                  const isFB = cell?.isFirstBlood ?? false
                  const isFlashing = flashingKeys?.has(key) ?? false

                  if (isSelf) {
                    return (
                      <td key={victim.teamId} className={classes.cellSelf}>
                        <span>—</span>
                      </td>
                    )
                  }

                  if (hits === 0) {
                    return (
                      <td key={victim.teamId} className={classes.cellEmpty}>
                        <span>0</span>
                      </td>
                    )
                  }

                  return (
                    <td
                      key={victim.teamId}
                      className={cx(classes.cellHit, {
                        [classes.cellFirstBlood]: isFB,
                        [classes.cellFlash]: isFlashing,
                      })}
                    >
                      <Tooltip
                        label={`${attackerName} attacked ${victimName} (${hits} captures)${isFB ? ' [First Blood]' : ''}`}
                        withArrow
                      >
                        <span>
                          {hits}
                          {isFB && ' ★'}
                        </span>
                      </Tooltip>
                    </td>
                  )
                })}

                <td className={classes.summaryCol}>
                  <Badge color="red" variant={hitsScored > 0 ? 'filled' : 'light'} size="sm">
                    {hitsScored}
                  </Badge>
                </td>
              </tr>
            )
          })}
        </tbody>
        <tfoot>
          <tr>
            <th className={classes.rowHeader} scope="row">
              TOTAL BREACHED
            </th>
            {teams.map((victim) => {
              const victimName = victim.teamName
              const timesBreached = victimTotals[victimName] ?? 0
              return (
                <td key={victim.teamId} className={classes.summaryCol}>
                  <Badge color={timesBreached > 0 ? 'orange' : 'teal'} variant="light" size="sm">
                    {timesBreached}
                  </Badge>
                </td>
              )
            })}
            <td className={classes.summaryCol}>
              <Badge color="grape" variant="filled" size="sm">
                {totalHits}
              </Badge>
            </td>
          </tr>
        </tfoot>
      </table>
    </div>
  )
}
