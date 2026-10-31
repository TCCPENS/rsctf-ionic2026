import { Anchor, Box, Paper, Stack, Text, Title } from '@mantine/core'
import { mdiArrowLeft } from '@mdi/js'
import { Icon } from '@mdi/react'
import { FC, PropsWithChildren, ReactNode, useId } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { LogoHeader } from '@Components/LogoHeader'
import { useConfig } from '@Hooks/useConfig'
import classes from '@Styles/AccountView.module.css'

interface AccountViewProps extends PropsWithChildren {
  title: ReactNode
  description?: ReactNode
  onSubmit?: (event: React.SubmitEvent<HTMLFormElement>) => Promise<void>
}

export const AccountView: FC<AccountViewProps> = ({ title, description, onSubmit, children }) => {
  const { config } = useConfig()
  const { t } = useTranslation()
  const titleId = useId()

  return (
    <main id="main-content" tabIndex={-1} className={classes.shell}>
      <Box component="aside" className={classes.context} aria-label={t('common.content.platform', 'Platform')}>
        <Link to="/" className={classes.brandLink}>
          <LogoHeader />
        </Link>
        <Stack gap="md" className={classes.contextCopy}>
          <Text className={classes.eyebrow}>IONIC 2026 · PENS</Text>
          <Text className={classes.statement}>Connect the World, Create the Future.</Text>
          <Text size="md" className={classes.contextDescription}>
            Selamat datang di Arena Kompetisi Cyber Security IONIC 2026. Masuk dengan akun tim Anda untuk memulai tantangan dan memantau persaingan secara langsung.
          </Text>
          <ol className={classes.steps}>
            <li>
              <span>01</span>
              Siapkan koneksi WireGuard VPN tim kamu
            </li>
            <li>
              <span>02</span>
              Amankan layanan sendiri & serang target lawan
            </li>
            <li>
              <span>03</span>
              Submit flag captured & raih podium juara!
            </li>
          </ol>
        </Stack>
        <Text size="xs" className={classes.contextFooter}>
          {config?.title?.trim() || 'IONIC 2026'} · Politeknik Elektronika Negeri Surabaya
        </Text>
      </Box>

      <section className={classes.formPanel} aria-labelledby={titleId}>
        <Paper className={classes.card} p={{ base: 'lg', sm: 'xl' }}>
          <Anchor component={Link} to="/" className={classes.backLink}>
            <Icon path={mdiArrowLeft} size={0.72} aria-hidden="true" />
            {t('common.button.back_home', 'Back to home')}
          </Anchor>

          <div className={classes.mobileBrand}>
            <LogoHeader />
          </div>

          <Stack gap={5} mb="lg">
            <Title id={titleId} order={1} className={classes.title}>
              {title}
            </Title>
            <Text c="dimmed" size="sm">
              {description ?? (config?.slogan?.trim() || 'Capture. Compete. Conquer.')}
            </Text>
          </Stack>

          <form className={classes.form} onSubmit={onSubmit} aria-labelledby={titleId} data-guide-interaction-scope>
            <Stack gap="sm">{children}</Stack>
          </form>
        </Paper>
      </section>
    </main>
  )
}
