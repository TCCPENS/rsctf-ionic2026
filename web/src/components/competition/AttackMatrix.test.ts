import assert from 'node:assert/strict'
import test from 'node:test'
import type { AdServiceScoreModel, AdTeamScoreModel } from '@Api'
import {
  buildTeamMatrix,
  calculateServiceHealth,
  type LiveMatrixAttackEvent,
} from '../../utils/matrixModel'

test('buildTeamMatrix returns empty structure when teams array is empty', () => {
  const result = buildTeamMatrix([], [])
  assert.equal(result.teams.length, 0)
  assert.equal(Object.keys(result.cells).length, 0)
  assert.equal(result.totalHits, 0)
})

test('buildTeamMatrix initializes N x N grid with self cells and aggregates attacks', () => {
  const teams: AdTeamScoreModel[] = [
    {
      rank: 1,
      participationId: 10,
      teamId: 1,
      teamName: 'Alpha',
      settledTotal: 100,
      projectedTotal: 100,
      offenseRate: 1,
      defenseRate: 1,
      slaRate: 1,
      services: [],
      epochs: [],
    },
    {
      rank: 2,
      participationId: 20,
      teamId: 2,
      teamName: 'Bravo',
      settledTotal: 80,
      projectedTotal: 80,
      offenseRate: 0.8,
      defenseRate: 0.8,
      slaRate: 1,
      services: [],
      epochs: [],
    },
  ]

  const events: LiveMatrixAttackEvent[] = [
    {
      id: 'ev-1',
      timestamp: 1000,
      attackerTeam: 'Alpha',
      victimTeam: 'Bravo',
      challengeTitle: 'Web Portal',
      blood: 'FirstBlood',
    },
    {
      id: 'ev-2',
      timestamp: 2000,
      attackerTeam: 'Alpha',
      victimTeam: 'Bravo',
      challengeTitle: 'Pwn Daemon',
      blood: 'Normal',
    },
  ]

  const matrix = buildTeamMatrix(teams, events)

  assert.equal(matrix.teams.length, 2)
  assert.equal(matrix.totalHits, 2)

  // Diagonal cell (self)
  const cellAlphaSelf = matrix.cells['Alpha:Alpha']
  assert.ok(cellAlphaSelf)
  assert.equal(cellAlphaSelf.isSelf, true)
  assert.equal(cellAlphaSelf.hits, 0)

  // Cell Alpha attacking Bravo
  const cellAlphaBravo = matrix.cells['Alpha:Bravo']
  assert.ok(cellAlphaBravo)
  assert.equal(cellAlphaBravo.isSelf, false)
  assert.equal(cellAlphaBravo.hits, 2)
  assert.equal(cellAlphaBravo.isFirstBlood, true)

  // Cell Bravo attacking Alpha (0 hits)
  const cellBravoAlpha = matrix.cells['Bravo:Alpha']
  assert.ok(cellBravoAlpha)
  assert.equal(cellBravoAlpha.isSelf, false)
  assert.equal(cellBravoAlpha.hits, 0)
  assert.equal(cellBravoAlpha.isFirstBlood, false)

  // Summary aggregation
  assert.equal(matrix.attackerTotals['Alpha'], 2)
  assert.equal(matrix.attackerTotals['Bravo'], 0)
  assert.equal(matrix.victimTotals['Bravo'], 2)
  assert.equal(matrix.victimTotals['Alpha'], 0)
})

test('calculateServiceHealth maps lastCheckStatus to status, color, and label', () => {
  const mockService = (status?: string | null): AdServiceScoreModel => ({
    challengeId: 1,
    settledPoints: 10,
    projectedPoints: 10,
    settledLocalPoints: 10,
    projectedLocalPoints: 10,
    offenseRate: 1,
    defenseRate: 1,
    slaRate: 1,
    captureCount: 0,
    lastCheckStatus: status,
  })

  assert.deepEqual(calculateServiceHealth(mockService('Ok')), {
    status: 'Ok',
    color: 'teal',
    label: 'UP',
  })

  assert.deepEqual(calculateServiceHealth(mockService('Mumble')), {
    status: 'Mumble',
    color: 'yellow',
    label: 'MUMBLE',
  })

  assert.deepEqual(calculateServiceHealth(mockService('Offline')), {
    status: 'Offline',
    color: 'red',
    label: 'DOWN',
  })

  assert.deepEqual(calculateServiceHealth(mockService(null)), {
    status: 'Unknown',
    color: 'gray',
    label: 'UNKNOWN',
  })
})
