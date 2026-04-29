import UserAvatar from '@/components/UserAvatar'
import type { HierarchyNode } from '@/types'

interface HierarchyTreeProps {
  node: HierarchyNode
  accentColor: string
  depth?: number
}

export default function HierarchyTree({ node, accentColor, depth = 0 }: HierarchyTreeProps) {
  const children = node.children ?? []
  const hasChildren = children.length > 0
  const cargoLabel = node.cargo?.name ?? '—'

  return (
    <div className={depth === 0 ? '' : 'relative mt-3'}>
      {depth > 0 && (
        <span
          className="absolute -left-3 top-0 bottom-0 w-px rounded-full"
          style={{ backgroundColor: `${accentColor}40` }}
          aria-hidden
        />
      )}
      <div
        className={`relative overflow-hidden rounded-2xl border bg-white shadow-sm ${depth === 0 ? 'border-2' : 'border-gray-100 ring-1 ring-gray-100'}`}
        style={depth === 0 ? { borderColor: `${accentColor}66` } : undefined}
      >
        <div
          className="h-1 w-full rounded-t-2xl"
          style={{ background: `linear-gradient(90deg, ${accentColor}, ${accentColor}99)` }}
        />
        <div className="flex flex-wrap items-start gap-3 p-4">
          <UserAvatar name={node.name} src={node.display_avatar} size="sm" />
          <div className="min-w-0 flex-1">
            <div className="flex flex-wrap items-center gap-2">
              <span className="font-semibold text-gray-900">{node.name}</span>
              {node.is_lead ? (
                <span
                  className="rounded-full px-2.5 py-0.5 text-xs font-semibold text-white shadow-sm"
                  style={{ backgroundColor: accentColor }}
                >
                  Gestor
                </span>
              ) : null}
              {node.orphan_hierarchy ? (
                <span className="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-900 ring-1 ring-amber-200/80">
                  Gestor não ligado na hierarquia
                </span>
              ) : null}
            </div>
            <p className="truncate text-sm text-gray-500">{node.email}</p>
            <p className="mt-1 text-xs font-medium text-gray-700">{cargoLabel}</p>
          </div>
        </div>
      </div>

      {hasChildren ? (
        <div className="ml-4 border-l border-dashed pl-5 pt-2" style={{ borderColor: `${accentColor}35` }}>
          <div className="space-y-2">
            {children.map((child) => (
              <HierarchyTree key={child.id} node={child} accentColor={accentColor} depth={depth + 1} />
            ))}
          </div>
        </div>
      ) : null}
    </div>
  )
}
