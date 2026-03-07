export function formatDuration(totalSeconds: number): string {
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;
    return `${hours}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
}

export function formatPace(secondsPerKm: number | null): string {
    if (secondsPerKm === null || secondsPerKm === 0) return '-';
    const minutes = Math.floor(secondsPerKm / 60);
    const seconds = Math.round(secondsPerKm % 60);
    return `${minutes}:${String(seconds).padStart(2, '0')}/km`;
}

export function formatDistance(km: number): string {
    return km.toFixed(2);
}

export function formatDate(isoString: string): string {
    const date = new Date(isoString);
    return date.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

export function formatShortDate(date: Date): string {
    return date.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
}

export function formatTimeRange(startIso: string, endIso: string): string {
    const options: Intl.DateTimeFormatOptions = { hour: '2-digit', minute: '2-digit' };
    const start = new Date(startIso).toLocaleTimeString('en-GB', options);
    const end = new Date(endIso).toLocaleTimeString('en-GB', options);
    return `${start} – ${end}`;
}
