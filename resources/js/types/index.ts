import type { PageProps as InertiaPageProps } from '@inertiajs/core'

export interface User {
    id: number
    name: string
    email: string
    roles: string[]
    is_demo: boolean
    company_id: number | null
    candidate_profile_id: number | null
}

export interface Flash {
    success?: string
    error?: string
}

export interface PageProps extends InertiaPageProps {
    auth: {
        user: User | null
    }
    locale: string
    supportedLocales: string[]
    translations: Record<string, Record<string, string>>
    flash: Flash
    notificationCount: number
    errors: Record<string, string>
}

// --- Candidate/Applications page payload (serialized in ApplicationController) ---

export interface InterviewDetails {
    at: string | null
    mode: string | null
    location: string | null
    url: string | null
    instructions: string | null
    formatted_at: string | null
}

export interface CompanySummary {
    id: number
    name: string
    slug: string
    logo_url: string | null
}

// --- Public posts pages (serialized in PostController) ---

export interface PostSummary {
    id: number
    title: string
    excerpt: string
    featured_image_url: string
    published_at_label: string
    url: string
}

export interface PostDetail extends PostSummary {
    content_html: string
    author_name: string
}

export interface PostLanguageLink {
    locale: string
    href: string
}

export interface RecruiterNoteTemplate {
    id: number
    name: string
    body: string
    update_url: string
    destroy_url: string
}

// --- Public companies pages (serialized in CompanyController) ---

export interface CompanyCardSummary extends CompanySummary {
    tagline: string | null
    location: string | null
    jobs_count: number
    jobs_count_label: string
    latest_jobs: { id: number; title: string }[]
}

export interface JobSummary {
    id: number
    title: string
    company: CompanySummary | null
    location: string | null
    remote_type: string | null
    category: string | null
    salary_min: number | null
    salary_max: number | null
    closes_at: string | null
    is_closing_soon: boolean
    // Present on the public jobs pages (JobController serializers); optional
    // so the candidate/recruiter application serializers keep compiling.
    closes_label?: string | null
    is_saved?: boolean
    has_applied?: boolean
    published_at?: string | null
}

export interface CompanyDetail extends CompanySummary {
    tagline: string | null
    mission: string | null
    culture: string | null
    founded_year: number | null
    website_url: string | null
    linkedin_url: string | null
    location: string | null
    size: string | null
    jobs: JobSummary[]
}

// --- Public jobs pages (serialized in JobController) ---

export interface JobDetail extends JobSummary {
    // Trusted server-side output of App\Support\JobDescriptionFormatter
    // (every user-controlled line is escaped) — the ONLY v-html on the page.
    description_html: string
    posted_label: string
    company: CompanyDetail | null
}

export interface JobFilters {
    search: string | null
    location: string | null
    category: string | null
    salary_min: string | null
    salary_max: string | null
}

export interface TimelineEvent {
    to_status: string
    from_status: string | null
    created_at: string
    formatted_at: string
    label: string
    changed_by_name: string | null
}

export interface CandidateApplication {
    id: number
    status: string
    status_label: string
    cover_letter: string | null
    notes: string | null
    notes_added: boolean
    created_at: string | null
    applied_label: string
    interview: InterviewDetails | null
    job: JobSummary
    timeline: TimelineEvent[]
}

export interface CandidateDashboardApplication {
    id: number
    status: string
    status_label: string
    applied_label: string | null
    job: {
        id: number
        title: string
        company: { name: string } | null
    }
}

export interface StatusCount {
    key: string
    label_key: string
    count: number
}

export interface Pagination {
    total: number
    per_page: number
    current_page: number
    last_page: number
    next_page_url: string | null
    prev_page_url: string | null
}

// --- Recruiter/Applications page payload (serialized in Recruiter/ApplicationController) ---

export interface RecruiterCandidateProfile {
    headline: string | null
    summary: string | null
    skills: string | null
    location: string | null
}

export interface RecruiterCandidate {
    id: number
    name: string
    email: string
    phone: string | null
    has_resume: boolean
    resume_url: string | null
    profile: RecruiterCandidateProfile | null
}

export interface RecruiterApplication {
    id: number
    status: string
    status_label: string
    candidate: RecruiterCandidate
    cover_letter: string | null
    notes: string | null
    notes_added: boolean
    status_changed_at: string | null
    created_at: string | null
    applied_label: string
    timeline: TimelineEvent[]
    interview: InterviewDetails | null
    can_review: boolean
    is_withdrawn: boolean
}

export interface RecruiterNoteTemplate {
    id: number
    name: string
    body: string
}

export interface ReviewFormData {
    status: string
    interview_mode: string
    notes: string
    interview_at: string
    interview_location: string
    interview_url: string
    interview_instructions: string
}

export interface RecruiterDashboardApplication {
    id: number
    status: string
    status_label: string
    created_at: string | null
    created_at_label: string
    candidate: {
        name: string
        initial: string
    }
    job: {
        id: number
        title: string
    }
}


export interface RecruiterJobSummary {
    id: number
    title: string
    location: string | null
    remote_type: string | null
    category: string | null
    status: 'published' | 'draft'
    applications_count: number
    published_at: string | null
    closes_at: string | null
    is_expired: boolean
    created_at: string | null
    // Composed server-side in the current locale (recruiter.* keys).
    posted_label: string
    published_label: string
    closes_label: string | null
    applications_label: string
}

export interface RecruiterJobDetail extends RecruiterJobSummary {
    // Raw plain-text description for the edit form.
    description: string
    // Trusted server-side output of App\Support\JobDescriptionFormatter
    // (every user-controlled line is escaped) — the ONLY v-html on the page.
    description_html: string
    location: string | null
    category: string | null
    remote_type: string | null
    salary_min: number | null
    salary_max: number | null
    // Y-m-d (toDateString) so <input type="date"> binds directly.
    closes_at: string | null
    closes_label: string | null
}

export interface JobFormData {
    title: string
    description: string
    location: string
    category: string
    remote_type: string
    salary_min: string
    salary_max: string
    closes_at: string
    status: 'draft' | 'published'
}

export interface ProfileUser {
    id: number
    name: string
    email: string
    phone: string | null
    profile_summary: string | null
    pending_email: string | null
    is_demo: boolean
    roles: string[]
}

export interface ProfileLanguage {
    language: string
    proficiency: string
}

export interface ProfileLink {
    name: string
    url: string
}

export interface ProfileExperience {
    job_title: string
    company_name: string
    location: string
    start_date: string
    end_date: string | null
    is_current: boolean
    description: string
}

export interface ProfileEducation {
    school: string
    degree: string
    field_of_study: string
    start_date: string
    end_date: string | null
    is_current: boolean
    description: string
}

export interface CandidateProfileSettings {
    headline: string | null
    skills: string | null
    languages_data: ProfileLanguage[]
    profile_links: ProfileLink[]
    experiences: ProfileExperience[]
    educations: ProfileEducation[]
    preferred_categories: string[]
    resume_path: string | null
    resume_url: string | null
}

export interface CompanyProfileSettings {
    id: number
    name: string
    tagline: string | null
    location: string | null
    website_url: string | null
    linkedin_url: string | null
    mission: string | null
    culture: string | null
    logo_url: string | null
}

export interface ProfileCompletion {
    percentage: number
    completed: number
    total: number
    missing: string[]
    steps_label: string
}

export interface ProfilePreviewApplicant {
    name: string
    email: string
    phone: string | null
    profile_summary: string | null
    candidateProfile: Pick<CandidateProfileSettings, 'headline' | 'skills' | 'languages_data' | 'profile_links' | 'experiences' | 'educations'> | null
}

