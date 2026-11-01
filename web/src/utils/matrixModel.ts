import type { AdServiceScoreModel, AdTeamScoreModel } from '@Api'

export interface LiveMatrixAttackEvent {
  id: string
  timestamp: number
  attackerTeam: string
  victimTeam: string
  challengeTitle: string
  blood: string
}

export interface MatrixCellData {
  attackerTeam: string
  victimTeam: string
  hits: number
  isFirstBlood: boolean
  isSelf: boolean
}

export interface TeamMatrixData {
  teams: AdTeamScoreModel[]
  cells: Record<string, MatrixCellData>
  attackerTotals: Record<string, number>
  victimTotals: Record<string, number>
  totalHits: number
}

/**
 * Builds the N x N cross matrix of attackers vs victims.
 * Key format: `${attackerTeam}:${victimTeam}`
 */
export function buildTeamMatrix(
  teams: AdTeamScoreModel[],
  liveEvents: LiveMatrixAttackEvent[] = []
): TeamMatrixData {
  const cells: Record<string, MatrixCellData> = {}
  const attackerTotals: Record<string, number> = {}
  const victimTotals: Record<string, number> = {}
  let totalHits = 0

  for (const t of teams) {
    attackerTotals[t.teamName] = 0
    victimTotals[t.teamName] = 0
  }

  // Initialize N x N grid
  for (const attacker of teams) {
    for (const victim of teams) {
      const key = `${attacker.teamName}:${victim.teamName}`
      const isSelf = attacker.teamName === victim.teamName
      cells[key] = {
        attackerTeam: attacker.teamName,
        victimTeam: victim.teamName,
        hits: 0,
        isFirstBlood: false,
        isSelf,
      }
    }
  }

  // Aggregate attacks
  for (const ev of liveEvents) {
    const key = `${ev.attackerTeam}:${ev.victimTeam}`
    const cell = cells[key]
    if (cell && !cell.isSelf) {
      cell.hits += 1
      if (ev.blood === 'FirstBlood') {
        cell.isFirstBlood = true
      }
      attackerTotals[ev.attackerTeam] = (attackerTotals[ev.attackerTeam] || 0) + 1
      victimTotals[ev.victimTeam] = (victimTotals[ev.victimTeam] || 0) + 1
      totalHits += 1
    }
  }

  return {
    teams,
    cells,
    attackerTotals,
    victimTotals,
    totalHits,
  }
}

export interface ServiceHealthResult {
  status: string
  color: string
  label: string
}

export function calculateServiceHealth(service?: AdServiceScoreModel | null): ServiceHealthResult {
  switch (service?.lastCheckStatus) {
    case 'Ok':
      return { status: 'Ok', color: 'teal', label: 'UP' }
    case 'Mumble':
      return { status: 'Mumble', color: 'yellow', label: 'MUMBLE' }
    case 'Offline':
      return { status: 'Offline', color: 'red', label: 'DOWN' }
    default:
      return { status: 'Unknown', color: 'gray', label: 'UNKNOWN' }
  }
}
