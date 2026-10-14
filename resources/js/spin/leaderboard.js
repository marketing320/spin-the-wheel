import '../echo';
import { subscribeToSpins } from './live-sync';

const list = document.getElementById('leaderboard-list');

if (list) {
    const status = document.getElementById('leaderboard-status');
    let lastWinnersSignature = null;

    function renderLeaderboard(winners) {
        list.replaceChildren();

        if (winners.length === 0) {
            const empty = document.createElement('li');
            empty.className = 'px-4 py-[clamp(1.5rem,8vh,3rem)] text-center font-display text-[clamp(0.62rem,2.7vw,0.75rem)] leading-relaxed text-slate-500';
            empty.textContent = 'No prizes have been won yet. Be the first!';
            list.appendChild(empty);
            return;
        }

        winners.forEach((winner) => {
            const row = document.createElement('li');
            row.className = 'live-winner-card grid grid-cols-[clamp(3.75rem,21vw,4.25rem)_minmax(0,1fr)] items-center gap-[clamp(0.375rem,2vw,0.5rem)] rounded-xl border-2 border-slate-100 px-[clamp(0.75rem,2vw,1rem)] py-[clamp(0.75rem,1.8vh,1rem)]';

            const time = document.createElement('time');
            time.className = 'font-display text-[clamp(0.48rem,2.2vw,0.55rem)] leading-relaxed text-brand-600';
            const wonAt = new Date(winner.won_at);
            time.dateTime = winner.won_at || '';
            time.textContent = Number.isNaN(wonAt.getTime())
                ? ''
                : new Intl.DateTimeFormat(undefined, { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }).format(wonAt);

            const name = document.createElement('span');
            name.className = 'min-w-0 truncate font-display text-[clamp(0.62rem,2.7vw,0.75rem)] font-bold text-slate-900';
            name.textContent = winner.name || 'Prize winner';

            const prize = document.createElement('span');
            prize.className = 'col-start-2 row-start-2 flex min-w-0 items-center gap-[clamp(0.25rem,1.5vw,0.5rem)]';

            const prizeName = document.createElement('span');
            prizeName.className = 'truncate text-[clamp(0.62rem,2.7vw,0.75rem)] font-semibold text-slate-600';
            prizeName.textContent = winner.prize || 'Prize';

            const rarity = document.createElement('span');
            const rarityStyles = {
                common: 'bg-slate-100 text-slate-700 ring-slate-300',
                uncommon: 'bg-emerald-100 text-emerald-700 ring-emerald-300',
                rare: 'bg-sky-100 text-sky-700 ring-sky-300',
                epic: 'bg-violet-100 text-violet-700 ring-violet-300',
                legendary: 'bg-amber-100 text-amber-800 ring-amber-400',
            };
            const rarityName = String(winner.rarity || 'common').toLowerCase();
            rarity.className = `inline-flex shrink-0 items-center rounded-full px-[clamp(0.375rem,2vw,0.5rem)] py-0.5 font-display text-[clamp(0.45rem,2vw,0.55rem)] uppercase ring-2 ${rarityStyles[rarityName] || rarityStyles.common}`;
            rarity.textContent = rarityName;

            prize.append(prizeName, rarity);
            row.append(time, name, prize);
            list.appendChild(row);
        });
    }

    async function refreshLeaderboard() {
        try {
            const response = await fetch(list.dataset.leaderboardUrl, {
                headers: { Accept: 'application/json' },
                cache: 'no-store',
            });
            if (!response.ok) {
                throw new Error(`Leaderboard refresh failed (${response.status})`);
            }

            const data = await response.json();
            const winners = Array.isArray(data.winners) ? data.winners : [];
            status.textContent = 'Live winners · updates automatically';

            const signature = JSON.stringify(winners);
            if (signature === lastWinnersSignature) {
                return;
            }
            lastWinnersSignature = signature;
            renderLeaderboard(winners);
            renderLeaderboard(winners);
        } catch (error) {
            console.error(error);
            status.textContent = 'Unable to refresh winners. Retrying shortly.';
        }
    }

    refreshLeaderboard();
    subscribeToSpins({ onCompleted: refreshLeaderboard });
    window.setInterval(refreshLeaderboard, 5000);
}
