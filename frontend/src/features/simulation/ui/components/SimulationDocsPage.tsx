import type { ReactNode } from 'react';
import { Button } from './Button';

type SimulationDocsPageProps = {
  onBack: () => void;
};

type SectionProps = {
  title: string;
  children: ReactNode;
};

function Section({ title, children }: SectionProps) {
  return (
    <section className="docs-section">
      <h2 className="docs-section-title">{title}</h2>
      <div className="docs-section-body">{children}</div>
    </section>
  );
}

export function SimulationDocsPage({ onBack }: SimulationDocsPageProps) {
  return (
    <main className="docs-main">
      <div className="docs-container">
        <header className="docs-hero">
          <div>
            <p className="docs-overline">Project Documentation</p>
            <h1 className="docs-title">Elevator Simulator: End-To-End Behavior</h1>
            <p className="docs-subtitle">
              Full implementation-level reference for runtime flow, dispatch, conditions, movement, doors, zones, and
              UI animation behavior.
            </p>
          </div>
          <Button type="button" className="docs-back-button" onClick={onBack}>
            Back
          </Button>
        </header>

        <Section title="1. Architecture Overview">
          <p>
            The system has three execution layers:
          </p>
          <ul className="docs-list">
            <li>State/config CRUD layer: create/update/reset/list simulations in DB.</li>
            <li>Tick runtime layer: runtime state lives in Redis and advances one tick at a time.</li>
            <li>UI layer: React reads queue preview and websocket ticks, then animates elevator positions.</li>
          </ul>
          <p>
            Main APIs:
          </p>
          <ul className="docs-list">
            <li><code className="docs-code">GET /api/v1/simulations</code></li>
            <li><code className="docs-code">POST /api/v1/simulations</code></li>
            <li><code className="docs-code">POST /api/v1/simulations/{'{simulation}'}/config</code></li>
            <li><code className="docs-code">POST /api/v1/simulations/{'{simulation}'}/start|pause|reset</code></li>
            <li><code className="docs-code">GET /api/v1/simulations/{'{simulation}'}/queue-preview</code></li>
            <li><code className="docs-code">POST /api/v1/simulations/{'{simulation}'}/calls/manual</code></li>
            <li><code className="docs-code">PATCH /api/v1/simulations/{'{simulation}'}/condition</code></li>
            <li><code className="docs-code">PATCH /api/v1/simulations/{'{simulation}'}/elevators/{'{elevatorId}'}/condition</code></li>
          </ul>
          <p>
            Tick execution is driven by background loop:
          </p>
          <ul className="docs-list">
            <li><code className="docs-code">php artisan simulation:run-loop --intervalMs=...</code></li>
            <li>For each running simulation, loop calls one atomic runtime step (<code className="docs-code">runSingleTick</code>).</li>
          </ul>
          <p>
            Tech stack:
          </p>
          <ul className="docs-list">
            <li>Frontend: React + TypeScript + Vite + Tailwind CSS.</li>
            <li>Backend API: Laravel (PHP) with modular simulation domain services.</li>
            <li>Runtime state: Redis for tick state, queues, and publish/subscribe tick events.</li>
            <li>Persistence: PostgreSQL for simulations, runs, and historical records.</li>
            <li>Transport: REST APIs for control/configuration + websocket tick stream for live UI.</li>
          </ul>
        </Section>

        <Section title="2. Runtime Data Model">
          <div className="docs-grid-2">
            <div className="docs-card">
              <h3 className="docs-card-title">Simulation Runtime</h3>
              <ul className="docs-list">
                <li><code className="docs-code">tickNumber</code></li>
                <li><code className="docs-code">mode</code> and <code className="docs-code">algorithm</code></li>
                <li><code className="docs-code">isEmergencyMode</code></li>
                <li><code className="docs-code">doorHoldTicks</code> (derived from config)</li>
                <li><code className="docs-code">maxPendingCalls</code> (global default driven)</li>
                <li><code className="docs-code">pickedUpPassengers</code>, <code className="docs-code">droppedOffPassengers</code></li>
                <li><code className="docs-code">elevators[]</code>, <code className="docs-code">pendingHallCalls[]</code></li>
              </ul>
            </div>
            <div className="docs-card">
              <h3 className="docs-card-title">Hall Call</h3>
              <ul className="docs-list">
                <li><code className="docs-code">callId</code> (shared ID generator with source tag <code className="docs-code">call</code>/<code className="docs-code">manual</code>)</li>
                <li><code className="docs-code">originFloor</code>, <code className="docs-code">targetFloor</code>, <code className="docs-code">passengerWeight</code></li>
                <li><code className="docs-code">direction</code> (enum: <code className="docs-code">up</code>/<code className="docs-code">down</code>)</li>
                <li><code className="docs-code">ageTicks</code> (increments while pending)</li>
                <li><code className="docs-code">status</code>: <code className="docs-code">Pending</code>, <code className="docs-code">Assigned</code>, <code className="docs-code">Riding</code>, <code className="docs-code">Served</code></li>
                <li><code className="docs-code">assignedElevatorId</code> (nullable)</li>
              </ul>
            </div>
          </div>
        </Section>

        <Section title="3. Simulation Tick Pipeline">
          <ol className="docs-ordered">
            <li>Lock simulation state (<code className="docs-code">SimulationStateMutexService</code>).</li>
            <li>Load runtime from Redis and increment <code className="docs-code">tickNumber</code>.</li>
            <li>If emergency mode: clear pending hall calls immediately.</li>
            <li>If not emergency: increment <code className="docs-code">ageTicks</code> for pending calls.</li>
            <li>If not emergency: maybe generate one new auto call (mode-driven probability).</li>
            <li>If not emergency: attempt dispatch assignments.</li>
            <li>Step elevators:
              <ul className="docs-list">
                <li>Emergency path: forced recall tick logic.</li>
                <li>Normal path: overload evaluation, OOS transition progress, stop rebuild, door progress, boarding, movement, arrival handling.</li>
              </ul>
            </li>
            <li>If not emergency: run dispatch assignment again after movement/arrivals.</li>
            <li>Prune served calls.</li>
            <li>Build payload, save state to Redis, publish websocket tick channel.</li>
          </ol>
        </Section>

        <Section title="4. Call Lifecycle And Assignment Flow">
          <p>
            Assignment orchestrator methods sit above algorithm scoring and enforce real dispatch rules:
          </p>
          <ul className="docs-list">
            <li><code className="docs-code">assignPendingCalls</code>: main loop for <code className="docs-code">Pending</code> calls.</li>
            <li><code className="docs-code">findSameFloorElevator</code>: fast path if car already at pickup floor.</li>
            <li><code className="docs-code">selectCandidateForCall</code>: algorithm candidate selection with optional exclusion fallback.</li>
            <li><code className="docs-code">canAcceptCall</code>: capacity gate using current load + reserved assigned pickups + new passenger weight.</li>
            <li><code className="docs-code">assignCallToElevator</code>: assign call and append pickup stop or immediate board path.</li>
            <li><code className="docs-code">promoteToRidingImmediately</code>: same-floor immediate boarding, pickup counter increment, destination stop append.</li>
          </ul>
          <p>
            Immediate assignment behavior:
          </p>
          <ul className="docs-list">
            <li>If elevator is on origin floor and capacity allows, call enters <code className="docs-code">Riding</code> in same dispatch pass.</li>
            <li>If not on origin floor, call remains <code className="docs-code">Assigned</code> until doors open at origin floor.</li>
          </ul>
          <p>
            Boarding-at-floor behavior:
          </p>
          <ul className="docs-list">
            <li>Only when door state is <code className="docs-code">Open</code> and call is <code className="docs-code">Assigned</code> at current floor.</li>
            <li>If overweight and overload is not manual, call is requeued to <code className="docs-code">Pending</code> and unassigned.</li>
          </ul>
        </Section>

        <Section title="5. Dispatch Algorithms">
          <p>All algorithms minimize a <code className="docs-code">priorityScore</code> (lower is better) and skip unavailable elevators.</p>
          <div className="docs-card">
            <h3 className="docs-card-title"><code className="docs-code">nearestCar</code></h3>
            <p><code className="docs-code">score = distanceToPickup * 100 + queuedStopCount * 10</code></p>
            <p>Behavior: nearest floor dominates, queue length is a light penalty.</p>
          </div>
          <div className="docs-card">
            <h3 className="docs-card-title"><code className="docs-code">scan</code></h3>
            <p><code className="docs-code">score = distanceToPickup * 100 + directionPenalty</code></p>
            <p><code className="docs-code">directionPenalty = 0</code> when moving toward pickup (or idle), else <code className="docs-code">200</code>.</p>
            <p>Behavior: strong bias for cars already moving in compatible direction.</p>
          </div>
          <div className="docs-card">
            <h3 className="docs-card-title"><code className="docs-code">look</code></h3>
            <p><code className="docs-code">score = distanceToPickup * 100 + turnAroundPenalty + queuedStopCount * 20</code></p>
            <p><code className="docs-code">turnAroundPenalty = 150</code> when pickup requires reversing direction.</p>
            <p>Behavior: balances distance with turn-around cost and queue pressure.</p>
          </div>
        </Section>

        <Section title="6. Modes And Auto Call Generation">
          <p>Manual mode disables auto traffic and allows manual call API only.</p>
          <div className="docs-grid-2">
            <div className="docs-card">
              <h3 className="docs-card-title">Generation probability per tick</h3>
              <ul className="docs-list">
                <li><code className="docs-code">morningPeak</code>: 80%</li>
                <li><code className="docs-code">eveningPeak</code>: 80%</li>
                <li><code className="docs-code">regular</code>: 60%</li>
                <li><code className="docs-code">manual</code>: 0%</li>
              </ul>
            </div>
            <div className="docs-card">
              <h3 className="docs-card-title">Origin/Destination shape</h3>
              <ul className="docs-list">
                <li><code className="docs-code">morningPeak</code>: <code className="docs-code">F0 -&gt; random upper floor</code></li>
                <li><code className="docs-code">eveningPeak</code>: <code className="docs-code">random upper floor -&gt; F0</code></li>
                <li><code className="docs-code">regular</code>: random origin/target, origin != target</li>
                <li>Passenger weight: random <code className="docs-code">120..280</code> lb</li>
              </ul>
            </div>
          </div>
          <p>Traffic generation guard:</p>
          <ul className="docs-list">
            <li>Generator stops when active calls (<code className="docs-code">Pending + Assigned</code>) reach <code className="docs-code">maxPendingCalls</code>.</li>
            <li><code className="docs-code">Unassigned</code> metric in UI is only <code className="docs-code">Pending</code>, not <code className="docs-code">Assigned</code>.</li>
          </ul>
        </Section>

        <Section title="7. Overload Logic">
          <div className="docs-card">
            <h3 className="docs-card-title">Manual overload (user action)</h3>
            <ul className="docs-list">
              <li>Sets condition <code className="docs-code">Overloaded</code> and stores pre-overload load in <code className="docs-code">overloadSavedLoad</code>.</li>
              <li>Movement halts; once stopped, doors open and stay open until clear.</li>
              <li>Existing <code className="docs-code">Assigned</code> calls are not automatically requeued by manual-overload path.</li>
            </ul>
          </div>
          <div className="docs-card">
            <h3 className="docs-card-title">Auto overload (weight-based)</h3>
            <ul className="docs-list">
              <li>Condition toggles based on <code className="docs-code">currentLoad &gt; capacity</code>.</li>
              <li>Assigned-not-boarded calls for auto-overloaded elevators are requeued to <code className="docs-code">Pending</code>.</li>
              <li>Safety valve: if overloaded with no planned stops, load is clamped to capacity and condition normalizes.</li>
            </ul>
          </div>
        </Section>

        <Section title="8. Out-Of-Service (OOS) Logic">
          <p>Disable service triggers graceful transition, not hard cut-off.</p>
          <ol className="docs-ordered">
            <li>Elevator condition becomes <code className="docs-code">PendingOutOfService</code> and planned stops are reset.</li>
            <li><code className="docs-code">Assigned</code> (not boarded) calls for this elevator are requeued to <code className="docs-code">Pending</code>.</li>
            <li><code className="docs-code">Riding</code> calls are preserved and completed first (drop-off destinations remain planned).</li>
            <li>After no riding calls remain, elevator is sent to zone anchor floor.</li>
            <li>At anchor floor, elevator becomes <code className="docs-code">OutOfService</code> and is parked (closed door, idle state).</li>
          </ol>
          <p>Enable service sets elevator back to normal idle and starts closing doors.</p>
        </Section>

        <Section title="9. Emergency Recall Logic">
          <p>Emergency mode is simulation-wide and overrides elevator-level conditions except hard OOS parking.</p>
          <ul className="docs-list">
            <li>On activate: <code className="docs-code">isEmergencyMode = true</code>, pending hall calls cleared.</li>
            <li>Elevators forced to condition <code className="docs-code">Emergency</code> and recalled to floor <code className="docs-code">0</code> with descent step = <code className="docs-code">emergencyDescentMultiplier</code> floors/tick.</li>
            <li>When at floor 0: load cleared and doors opened.</li>
            <li>During emergency tick path, normal dispatch/traffic generation is bypassed.</li>
            <li>While emergency active, elevator-level condition updates are rejected (HTTP 409).</li>
            <li>On clear: emergency elevators return to normal and doors begin closing if open.</li>
          </ul>
        </Section>

        <Section title="10. Door State Machine">
          <ul className="docs-list">
            <li><code className="docs-code">OutOfService</code> forces door state to <code className="docs-code">Closed</code> and timer to <code className="docs-code">0</code> each progress pass.</li>
            <li><code className="docs-code">Opening</code> transitions to <code className="docs-code">Open</code> with <code className="docs-code">doorHoldTicks</code> timer.</li>
            <li><code className="docs-code">Open</code> decrements timer until zero, then transitions to <code className="docs-code">Closing</code>.</li>
            <li>Manual overload exception: while open, timer is held/reset to keep doors open.</li>
            <li><code className="docs-code">Closing</code> transitions to <code className="docs-code">Closed</code>; if elevator is not moving, state becomes <code className="docs-code">Idle</code>.</li>
          </ul>
        </Section>

        <Section title="11. Movement Logic">
          <ul className="docs-list">
            <li>Movement only occurs when door state is <code className="docs-code">Closed</code>.</li>
            <li>If no next stop: elevator goes <code className="docs-code">Idle/Idle</code> (state/direction).</li>
            <li>If current floor == next stop: stop is removed, direction set idle, doors start opening, arrival emitted.</li>
            <li>Else move exactly 1 floor per tick toward next stop.</li>
            <li>Manual overload: movement halts and doors open once fully stopped.</li>
            <li>OutOfService: no movement.</li>
          </ul>
        </Section>

        <Section title="12. Zones Logic">
          <ul className="docs-list">
            <li>Each shaft gets deterministic floor range via <code className="docs-code">ZoneCalculator::bounds</code>.</li>
            <li>Zone bounds partition floors by shaft index (<code className="docs-code">floor(shaft * floors / count)</code> pattern).</li>
            <li>Zone anchor floor is midpoint of the shaft bounds.</li>
            <li>OOS transition uses anchor as parking target.</li>
          </ul>
        </Section>

        <Section title="13. Speed And Animation Logic">
          <div className="docs-grid-2">
            <div className="docs-card">
              <h3 className="docs-card-title">Backend runtime speed</h3>
              <ul className="docs-list">
                <li>Normal movement advances one floor each tick in movement service.</li>
                <li>Emergency recall descent step uses <code className="docs-code">emergencyDescentMultiplier</code> floors/tick.</li>
                <li><code className="docs-code">tickIntervalMs</code>, <code className="docs-code">floorTravelSeconds</code>, <code className="docs-code">maxPendingCalls</code> are global config defaults now.</li>
              </ul>
            </div>
            <div className="docs-card">
              <h3 className="docs-card-title">Frontend motion smoothing</h3>
              <ul className="docs-list">
                <li>Animation hook interpolates per frame using snapshot speed.</li>
                <li>Near-stop slowdown applies when close to target (<code className="docs-code">nearStopDistance</code>).</li>
                <li>Emergency slowdown distance scales with emergency speed ratio for visible braking.</li>
                <li>Door animation duration scales with tick interval for stable visual timing.</li>
              </ul>
            </div>
          </div>
        </Section>

        <Section title="14. Visual State Mapping">
          <div className="docs-state-legend">
            <h3 className="docs-card-title">State Legend (Visual)</h3>
            <ol className="docs-state-list">
              <li className="docs-state-row">
                <span className="docs-state-label">1. Overloaded</span>
                <div className="docs-state-sample">
                  <div className="docs-mini-car docs-mini-car--overloaded">
                    <div className="docs-mini-floor">8</div>
                  </div>
                </div>
              </li>
              <li className="docs-state-row">
                <span className="docs-state-label">2. Door Open / Opening</span>
                <div className="docs-state-sample">
                  <div className="docs-mini-car docs-mini-car--door">
                    <div className="docs-mini-door docs-mini-door--left docs-mini-door--opening-anim" />
                    <div className="docs-mini-door docs-mini-door--right docs-mini-door--opening-anim" />
                    <div className="docs-mini-floor">3</div>
                  </div>
                </div>
              </li>
              <li className="docs-state-row">
                <span className="docs-state-label">3. Door Closed / Closing</span>
                <div className="docs-state-sample">
                  <div className="docs-mini-car docs-mini-car--door">
                    <div className="docs-mini-door docs-mini-door--left docs-mini-door--closing-anim" />
                    <div className="docs-mini-door docs-mini-door--right docs-mini-door--closing-anim" />
                    <div className="docs-mini-floor">3</div>
                  </div>
                </div>
              </li>
              <li className="docs-state-row">
                <span className="docs-state-label">4. Emergency</span>
                <div className="docs-state-sample">
                  <div className="docs-mini-arrow">▼</div>
                  <div className="docs-mini-car docs-mini-car--emergency-flash">
                    <div className="docs-mini-floor">7</div>
                  </div>
                </div>
              </li>
              <li className="docs-state-row">
                <span className="docs-state-label">5. OOS / Pending OOS (same visual)</span>
                <div className="docs-state-sample">
                  <div className="docs-mini-car docs-mini-car--oos-flash">
                    <div className="docs-mini-floor">6</div>
                  </div>
                </div>
              </li>
              <li className="docs-state-row">
                <span className="docs-state-label">6. IDLE</span>
                <div className="docs-state-sample">
                  <div className="docs-mini-car docs-mini-car--idle">
                    <div className="docs-mini-floor">1</div>
                  </div>
                </div>
              </li>
              <li className="docs-state-row">
                <span className="docs-state-label">7. MOVING</span>
                <div className="docs-state-sample">
                  <div className="docs-mini-arrow docs-mini-arrow--up">▲</div>
                  <div className="docs-mini-car docs-mini-car--moving">
                    <div className="docs-mini-floor">5</div>
                  </div>
                </div>
              </li>
            </ol>
          </div>
        </Section>

        <Section title="15. Metrics Semantics">
          <ul className="docs-list">
            <li><code className="docs-code">Unassigned</code> = count of calls with status <code className="docs-code">Pending</code>.</li>
            <li><code className="docs-code">Picked Up</code> / <code className="docs-code">Dropped Off</code> = cumulative counters.</li>
            <li><code className="docs-code">Total</code> in header = <code className="docs-code">Unassigned + Picked Up + Dropped Off</code> (UI aggregate).</li>
            <li><code className="docs-code">Assigned</code> queue pressure exists even if <code className="docs-code">Unassigned</code> stays zero.</li>
          </ul>
        </Section>

        <Section title="16. Lifecycle Semantics">
          <ul className="docs-list">
            <li>Create: persists simulation with config and immediately <code className="docs-code">reset</code>s runtime state in Redis.</li>
            <li>Start: marks simulation <code className="docs-code">running</code> and creates open run record if missing.</li>
            <li>Pause: marks <code className="docs-code">paused</code>.</li>
            <li>Reset: closes open run, transitions status through <code className="docs-code">completed</code> then <code className="docs-code">draft</code>, rebuilds fresh runtime.</li>
          </ul>
        </Section>
      </div>
    </main>
  );
}
