import {
  Badge,
  Box,
  Button,
  Card,
  Group,
  Modal,
  ModalProps,
  ScrollArea,
  Stack,
  Text,
  Textarea,
  ThemeIcon,
  Title,
} from '@mantine/core'
import { showNotification } from '@mantine/notifications'
import {
  mdiAlertCircleOutline,
  mdiCheckCircleOutline,
  mdiCloseCircleOutline,
  mdiFlagVariant,
  mdiSend,
} from '@mdi/js'
import { Icon } from '@mdi/react'
import { FC, useState } from 'react'
import { useTranslation } from 'react-i18next'
import api, { AdSubmitResultModel } from '@Api'
import misc from '@Styles/Misc.module.css'

interface AdFlagSubmitModalProps extends ModalProps {
  gameId: number
}

const statusBadge = (status?: string) => {
  switch (status) {
    case 'accepted':
      return { color: 'teal', label: 'ACCEPTED (+Points)' }
    case 'duplicate':
      return { color: 'yellow', label: 'DUPLICATE' }
    case 'wrong':
      return { color: 'red', label: 'WRONG FLAG' }
    case 'expired':
      return { color: 'orange', label: 'FLAG EXPIRED' }
    case 'self_attack':
      return { color: 'red', label: 'SELF ATTACK (Flag Sendiri)' }
    case 'paused':
      return { color: 'gray', label: 'SCORING PAUSED' }
    default:
      return { color: 'gray', label: status?.toUpperCase() || 'UNKNOWN' }
  }
}

export const AdFlagSubmitModal: FC<AdFlagSubmitModalProps> = ({ gameId, ...modalProps }) => {
  const { t } = useTranslation()
  const [rawFlags, setRawFlags] = useState('')
  const [submitting, setSubmitting] = useState(false)
  const [results, setResults] = useState<{
    acceptedCount: number
    results: AdSubmitResultModel[]
  } | null>(null)

  const handleSubmit = async () => {
    // Split by newlines, spaces, commas
    const flags = rawFlags
      .split(/[\r\n, ]+/)
      .map((f) => f.trim())
      .filter(Boolean)

    if (flags.length === 0) {
      showNotification({
        color: 'red',
        message: t('game.ad.flag_empty', 'Masukkan minimal satu string flag untuk disubmit.'),
      })
      return
    }

    if (flags.length > 100) {
      showNotification({
        color: 'orange',
        message: t('game.ad.flag_limit', 'Maksimal 100 flag per satu kali submit.'),
      })
      return
    }

    setSubmitting(true)
    try {
      const response = await api.game.gameAdSubmit(gameId, { flags })
      const data = response.data
      setResults(data)

      if (data.acceptedCount > 0) {
        showNotification({
          color: 'teal',
          title: t('game.ad.submit_success', 'Flag Diterima!'),
          message: t('game.ad.submit_success_desc', '{{count}} flag berhasil di-submit dan tercatat sebagai bukti penyerangan.', {
            count: data.acceptedCount,
          }),
          icon: <Icon path={mdiCheckCircleOutline} size={1} />,
        })
      } else {
        showNotification({
          color: 'orange',
          title: t('game.ad.submit_none_accepted', 'Tidak ada flag baru yang diterima'),
          message: t('game.ad.submit_none_desc', 'Periksa status detail setiap flag di bawah ini.'),
          icon: <Icon path={mdiAlertCircleOutline} size={1} />,
        })
      }
    } catch (e) {
      showNotification({
        color: 'red',
        title: t('common.error.title', 'Gagal Submit Flag'),
        message: t('game.ad.submit_error_desc', 'Terjadi kesalahan saat mengirim flag ke server.'),
        icon: <Icon path={mdiCloseCircleOutline} size={1} />,
      })
    } finally {
      setSubmitting(false)
    }
  }

  const handleClear = () => {
    setRawFlags('')
    setResults(null)
  }

  return (
    <Modal
      size="44rem"
      centered
      title={
        <Group gap="sm">
          <ThemeIcon variant="light" color="grape" size="lg">
            <Icon path={mdiFlagVariant} size={1} />
          </ThemeIcon>
          <Box>
            <Title order={4}>{t('game.ad.submit_title', 'Submit Flag Lawan')}</Title>
            <Text size="xs" c="dimmed">
              {t(
                'game.ad.submit_subtitle',
                'Kirim flag yang berhasil Anda curi dari tim lawan (bisa 1 flag atau banyak flag sekaligus)'
              )}
            </Text>
          </Box>
        </Group>
      }
      {...modalProps}
    >
      <Stack gap="md">
        <Textarea
          label={t('game.ad.flag_input_label', 'Flag Input')}
          description={t(
            'game.ad.flag_input_desc',
            'Paste flag di sini. Jika lebih dari satu, pisahkan dengan baris baru (enter).'
          )}
          placeholder={`flag{contoh_flag_tim_lawan_1}\nflag{contoh_flag_tim_lawan_2}`}
          minRows={4}
          maxRows={8}
          autosize
          value={rawFlags}
          onChange={(e) => setRawFlags(e.currentTarget.value)}
          styles={{ input: { fontFamily: 'monospace', fontSize: '0.85rem' } }}
        />

        <Group justify="space-between">
          <Button variant="subtle" color="gray" size="sm" onClick={handleClear} disabled={submitting}>
            {t('common.button.clear', 'Bersihkan')}
          </Button>

          <Button
            color="grape"
            size="sm"
            leftSection={<Icon path={mdiSend} size={0.8} />}
            loading={submitting}
            disabled={!rawFlags.trim()}
            onClick={handleSubmit}
          >
            {t('game.ad.submit_button', 'Submit Flag')}
          </Button>
        </Group>

        {results && (
          <Box mt="xs">
            <Group justify="space-between" mb="xs">
              <Text fw={700} size="sm">
                {t('game.ad.submit_result_title', 'Hasil Submit:')}
              </Text>
              <Badge color={results.acceptedCount > 0 ? 'teal' : 'gray'} variant="filled" size="sm">
                {results.acceptedCount} / {results.results.length} Accepted
              </Badge>
            </Group>

            <ScrollArea h="30vh" scrollbarSize={6}>
              <Stack gap="xs" pr="xs">
                {results.results.map((res, idx) => {
                  const badge = statusBadge(res.status)
                  return (
                    <Card key={idx} withBorder padding="xs" radius="md">
                      <Group justify="space-between" wrap="nowrap">
                        <Text size="xs" fw={600} className={misc.ffmono} truncate style={{ flex: 1 }}>
                          {res.flag}
                        </Text>
                        <Badge color={badge.color} variant="light" size="xs">
                          {badge.label}
                        </Badge>
                      </Group>
                      {res.message && (
                        <Text size="xs" c="dimmed" mt={4}>
                          {res.message}
                        </Text>
                      )}
                    </Card>
                  )
                })}
              </Stack>
            </ScrollArea>
          </Box>
        )}
      </Stack>
    </Modal>
  )
}
