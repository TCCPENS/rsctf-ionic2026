import { Badge, Box, Card, Group, Stack, Text, ThemeIcon } from '@mantine/core'
import { mdiSwordCross } from '@mdi/js'
import { Icon } from '@mdi/react'
import dayjs from 'dayjs'
import { FC } from 'react'
import { useTranslation } from 'react-i18next'
import { useLanguage } from '@Utils/I18n'
import type { LiveMatrixAttackEvent } from '@Utils/matrixModel'
import classes from '@Styles/Matrix.module.css'

interface MatrixTickerProps {
  events: LiveMatrixAttackEvent[]
}

export const MatrixTicker: FC<MatrixTickerProps> = ({ events }) => {
  const { t } = useTranslation()
  const { locale } = useLanguage()

  if (!events || events.length === 0) {
    return (
      <div className={classes.tickerSection}>
        <Group justify="center" gap="xs" py="xs">
          <ThemeIcon size="sm" variant="subtle" color="gray">
            <Icon path={mdiSwordCross} size={0.7} />
          </ThemeIcon>
          <Text size="xs" c="dimmed">
            {t('game.matrix.ticker_empty', 'Menunggu data serangan masuk secara live...')}
          </Text>
        </Group>
      </div>
    )
  }

  return (
    <div className={classes.tickerSection} aria-label={t('game.matrix.ticker_label', 'Recent Live Attacks')}>
      <Group justify="space-between" wrap="nowrap">
        <Text size="xs" fw={700} c="dimmed" tt="uppercase" style={{ letterSpacing: '0.05em' }}>
          {t('game.matrix.ticker_title', 'Serangan Terkini (Live Feed)')}
        </Text>
        <Badge size="xs" color="teal" variant="dot">
          Live Stream
        </Badge>
      </Group>

      <div className={classes.tickerStream} role="feed" aria-busy="false">
        {events.slice(0, 15).map((ev) => (
          <div key={ev.id} className={classes.tickerCard}>
            <Group justify="space-between" wrap="nowrap" mb={2}>
              <Badge
                color={ev.blood === 'FirstBlood' ? 'yellow' : 'red'}
                variant="filled"
                size="xs"
              >
                {ev.blood === 'FirstBlood' ? 'FIRST BLOOD' : 'HIT'}
              </Badge>
              <Text size="xs" c="dimmed" ff="monospace">
                {dayjs(ev.timestamp).locale(locale).format('LTS')}
              </Text>
            </Group>

            <Group gap={4} wrap="nowrap" mt={3}>
              <Text size="xs" fw={700} truncate maw={100} title={ev.attackerTeam}>
                {ev.attackerTeam}
              </Text>
              <Text size="xs" c="dimmed">
                ➔
              </Text>
              <Text size="xs" fw={700} c="red" truncate maw={100} title={ev.victimTeam}>
                {ev.victimTeam}
              </Text>
            </Group>

            <Text size="xs" c="dimmed" truncate mt={1}>
              {ev.challengeTitle}
            </Text>
          </div>
        ))}
      </div>
    </div>
  )
}
