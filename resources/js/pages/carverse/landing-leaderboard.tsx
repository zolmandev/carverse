import { router } from '@inertiajs/react';
import { Trophy } from 'lucide-react';
export type LandingRacing = {
    is_demo: boolean;
    distance: number;
    event: string | null;
    events: string[];
    runs: {
        id: number;
        driver: string;
        vehicle: string;
        event: string;
        venue: string;
        race_date: string;
        distance_metres: number;
        elapsed_ms: number;
    }[];
};
export default function LandingLeaderboard({ racing }: { racing: LandingRacing }) {
    function select(distance: number, event?: string | null) {
        router.get(
            '/',
            { race_distance: distance, ...(event ? { race_event: event } : {}) },
            { preserveScroll: true, preserveState: true, only: ['racing'] },
        );
    }
    return (
        <aside className="cv-landing-events cv-landing-leaderboard" aria-labelledby="landing-leaderboard-title">
            <div className="cv-events-sidebar-heading">
                <span className="cv-events-sidebar-icon">
                    <Trophy size={21} />
                </span>
                <div>
                    <p className="cv-eyebrow">ON THE CLOCK</p>
                    <h2 id="landing-leaderboard-title">Leaderboard</h2>
                </div>
            </div>
            <nav className="cv-race-distance-tabs" aria-label="Race distance">
                {[
                    { distance: 402, label: '¼ mile' },
                    { distance: 201, label: '⅛ mile' },
                    { distance: 805, label: '½ mile' },
                ].map((tab) => (
                    <button
                        key={tab.distance}
                        type="button"
                        aria-pressed={racing.distance === tab.distance}
                        className={racing.distance === tab.distance ? 'selected' : ''}
                        onClick={() => select(tab.distance)}
                    >
                        {tab.label}
                    </button>
                ))}
            </nav>
            {racing.events.length > 1 && (
                <label className="cv-field">
                    <span className="sr-only">Select racing event</span>
                    <select aria-label="Select racing event" value={racing.event || ''} onChange={(e) => select(racing.distance, e.target.value)}>
                        {racing.events.map((event) => (
                            <option key={event} value={event}>
                                {event}
                            </option>
                        ))}
                    </select>
                </label>
            )}
            {racing.runs.length ? (
                <ol className="cv-race-preview-list">
                    {racing.runs.map((run) => (
                        <li key={run.id}>
                            <span className="cv-race-rank">{racing.runs.findIndex((r) => r.elapsed_ms === run.elapsed_ms) + 1}</span>
                            <div>
                                <strong>{run.driver}</strong>
                                <p>{run.vehicle}</p>
                                <small>{run.venue}</small>
                            </div>
                            <time>
                                {(run.elapsed_ms / 1000).toFixed(3)}
                                <small>seconds</small>
                            </time>
                        </li>
                    ))}
                </ol>
            ) : null}
            <p className="cv-racing-disclaimer">Top 10 runs for the selected event and distance. Tied times share a rank.</p>
        </aside>
    );
}
