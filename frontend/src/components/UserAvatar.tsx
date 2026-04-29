import { useState } from 'react'

function initialsFromName(name: string): string {
  const parts = name.trim().split(/\s+/).filter(Boolean)
  if (parts.length >= 2) {
    const a = parts[0][0] ?? ''
    const b = parts[parts.length - 1][0] ?? ''
    return (a + b).toUpperCase()
  }
  if (parts.length === 1 && parts[0].length >= 2) {
    return parts[0].slice(0, 2).toUpperCase()
  }
  return (parts[0]?.[0] ?? '?').toUpperCase()
}

const sizeStyles = {
  sm: 'h-10 w-10 text-sm',
  md: 'h-16 w-16 text-xl',
  lg: 'h-24 w-24 text-3xl',
} as const

export type UserAvatarSize = keyof typeof sizeStyles

interface UserAvatarProps {
  name: string
  /** URL da foto (Google, storage, etc.). Sem URL válida → iniciais. */
  src?: string | null
  size?: UserAvatarSize
  className?: string
}

/**
 * Avatar com foto ou iniciais — não depende de arquivo estático em /public.
 */
export default function UserAvatar({ name, src, size = 'sm', className = '' }: UserAvatarProps) {
  const [failed, setFailed] = useState(false)
  const trimmed = src?.trim() ?? ''
  const showPhoto = trimmed.length > 0 && !failed
  const initials = initialsFromName(name || '?')

  return (
    <div
      className={`relative shrink-0 overflow-hidden rounded-full bg-gray-100 ${sizeStyles[size]} ${className}`}
    >
      {showPhoto ? (
        <img
          src={trimmed}
          alt=""
          className="h-full w-full object-cover"
          onError={() => setFailed(true)}
        />
      ) : (
        <div
          className="flex h-full w-full items-center justify-center bg-gradient-to-br from-primary/30 to-primary/5 font-semibold text-primary"
          aria-hidden
        >
          {initials}
        </div>
      )}
    </div>
  )
}
