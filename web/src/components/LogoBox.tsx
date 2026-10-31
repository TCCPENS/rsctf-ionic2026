import { Box, BoxProps, createPolymorphicComponent, ElementProps, em, Image } from '@mantine/core'
import { forwardRef } from 'react'
import { MainIcon, MainIconProps } from '@Components/icon/MainIcon'
import { useConfig } from '@Hooks/useConfig'

type LogoProps = MainIconProps & BoxProps & ElementProps<'div'> & { url?: string }

export const LogoBox = createPolymorphicComponent<'div', LogoProps>(
  forwardRef<HTMLDivElement, LogoProps>((props, ref) => {
    const { size, ignoreTheme, url, ...others } = props
    const { config } = useConfig()
    const custom = url || config.logoUrl

    return (
      <Box {...others} ref={ref} w={size} h={size}>
        {custom ? (
          <Image src={custom} alt={`${config.title?.trim() || 'IONIC 2026'} logo`} w={size} h="auto" maw={size} mah={size} />
        ) : (
          <Image src="/ionic-logo.png" alt="IONIC 2026 Logo" w={size} h="auto" maw={size} mah={size} fallbackSrc="/favicon.png" />
        )}
      </Box>
    )
  })
)
