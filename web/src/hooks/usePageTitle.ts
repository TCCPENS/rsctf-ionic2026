import { useDocumentTitle } from '@mantine/hooks'
import { useConfig } from '@Hooks/useConfig'

export const usePageTitle = (title?: string) => {
  const { config, error } = useConfig()

  const platform = error ? 'IONIC 2026' : `${config?.title?.trim() || 'IONIC 2026'}::CTF`

  useDocumentTitle(typeof title === 'string' && title.trim().length > 0 ? `${title} - ${platform}` : platform)
}
