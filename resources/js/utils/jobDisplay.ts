/**
 * Display formatting for the raw job fields every job payload carries
 * (`JobCardSerializer`, the job show pages, the application serializers). Kept in
 * one place so a job cannot be described one way in a list and another way on its
 * own page.
 */

/**
 * "…$80,000 - $120,000" when both bounds are known, the single bound otherwise,
 * and null when the job advertises no salary at all. Partial data used to render
 * a "$0 -" prefix, which reads as a real figure.
 */
export function formatSalaryRange(min: number | null, max: number | null): string | null {
    if (!min && !max) return null

    const amount = (value: number) => `$${Number(value).toLocaleString()}`

    if (min && max) return `${amount(min)} - ${amount(max)}`

    return amount((min ?? max) as number)
}

/**
 * The translated label for a job's `remote_type` when the page passed one, and a
 * capitalised raw value otherwise.
 */
export function formatRemoteTypeLabel(type: string | null, labels: Record<string, string> = {}): string | null {
    if (!type) return null

    return labels[type.toLowerCase()] ?? type.charAt(0).toUpperCase() + type.slice(1)
}
