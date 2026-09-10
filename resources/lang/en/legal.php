<?php

/**
 * Legal documents, structured as title + sections so one page component renders
 * either of them and both locales stay in step. Facts here are the ones the
 * platform actually implements (private resume storage, functional cookies only,
 * Google Fonts, rate limiting, demo reset). Add the operating entity and
 * governing law to the relevant sections before relying on these as a contract.
 */
return [
    'updated_date' => '10 September 2026',
    'updated_label' => 'Last updated: :date',
    'back_home' => 'Back to home',
    'questions' => 'Questions about this document, or a request about your data? Write to :email.',

    'privacy' => [
        'title' => 'Privacy Policy',
        'summary' => 'What personal data Recruivo processes, why, who can see it and what you can ask us to do with it.',
        'sections' => [
            [
                'heading' => '1. Who this policy covers',
                'body' => 'Recruivo is a job platform: candidates find and apply to jobs, recruiters publish jobs and manage applications, and administrators operate the platform. This policy explains how personal data is handled on recruivo.work and on the public demo at demo.recruivo.work. It applies to visitors, candidates, recruiters and administrators.',
            ],
            [
                'heading' => '2. Data you give us',
                'body' => 'When you create an account: your name, email address and a password (stored only as a hash). If you are a candidate we also store what you choose to add to your profile - phone number, location, headline, summary, skills, languages, links, work experience, education, preferred categories and your resume file. If you represent a company we store the company details you supply, such as name, tagline, location, size, website, LinkedIn page, mission, culture and logo.',
            ],
            [
                'heading' => '3. Data created by using the platform',
                'body' => 'When you apply to a job we store the application, the resume you attached, your cover letter, the status changes on that application, the notes a recruiter writes about it, interview details and the notifications we send you. Saved jobs and quick preferences are stored too, so the platform can show them back to you.',
            ],
            [
                'heading' => '4. Technical data',
                'body' => 'We record the IP address and browser user agent that reach us, which we use for security and rate limiting. We set functional cookies: a session cookie, a CSRF token cookie, and cookies that remember your language and colour theme. Application logs record requests and errors so we can diagnose faults; they are rotated and kept for a short period.',
            ],
            [
                'heading' => '5. How we use your data',
                'body' => 'To operate the platform: creating and securing your account, publishing jobs, delivering your applications to the recruiter whose job you applied to, sending transactional email (address verification, password reset, application updates), showing your notifications, preventing abuse, diagnosing faults and keeping the service available.',
            ],
            [
                'heading' => '6. Why we are allowed to',
                'body' => 'We process this data to perform the contract you enter into by using the platform, for our legitimate interests in securing and improving the service, to comply with legal obligations, and - where the law requires it - on the basis of consent you can withdraw at any time.',
            ],
            [
                'heading' => '7. Cookies and third-party requests',
                'body' => 'The cookies we set are the ones the site needs to work: a session cookie, a CSRF token cookie, and cookies that remember your language and colour theme. We use no advertising or analytics cookies and we run no third-party trackers. The fonts used on the site are served by Google Fonts, which receives your IP address and details of your browser to deliver the font files: those requests are made only if you accept them in the cookie banner, and you can change or withdraw that choice at any time ("Cookie settings" in the footer, or by clearing your cookies). The site is delivered through Cloudflare, which necessarily sees the traffic it proxies.',
            ],
            [
                'heading' => '8. Who can see your data',
                'body' => 'Recruiters see the applications submitted to their own job postings, including the resume and cover letter you sent with them. Administrators can see platform data as part of operating the service. Our hosting and email delivery providers process data on our instructions. We do not sell personal data, and we do not share it with advertisers.',
            ],
            [
                'heading' => '9. Storage and security',
                'body' => 'Data is stored on servers we operate. Resumes and other uploaded files are kept in private storage and are only reachable through routes that check who you are and what you are allowed to see. Traffic is encrypted with HTTPS. Sessions, cache and queues use a separate store from the database. No system is perfect, so we keep the data we hold to what the service actually needs.',
            ],
            [
                'heading' => '10. How long we keep it',
                'body' => 'Account and profile data is kept while your account is active, and afterwards only for as long as the law requires (for example accounting or dispute records). Deleting your account removes your profile, your applications, your uploaded resumes and your saved jobs. Rotated logs and backups expire on their own retention windows.',
            ],
            [
                'heading' => '11. Your rights',
                'body' => 'You can ask to see the data we hold about you, correct it, delete it, restrict or object to how we use it, receive it in a portable form, or withdraw consent you previously gave. You can delete your account yourself in your profile settings, and you can write to us for anything else. We answer within the time the applicable law allows, and you may complain to your local data protection authority.',
            ],
            [
                'heading' => '12. Children',
                'body' => 'Recruivo is not intended for children. You must be at least 16 years old to create an account, and we do not knowingly collect data from anyone younger.',
            ],
            [
                'heading' => '13. Changes to this policy',
                'body' => 'We may update this policy as the platform changes. The date at the top of this page always reflects the current version, and material changes are announced in the application.',
            ],
        ],
    ],

    'terms' => [
        'title' => 'Terms of Service',
        'summary' => 'The rules that apply when you use Recruivo as a visitor, candidate or recruiter.',
        'sections' => [
            [
                'heading' => '1. Accepting these terms',
                'body' => 'By using Recruivo, creating an account or applying to a job, you agree to these terms. If you do not agree with them, do not use the platform.',
            ],
            [
                'heading' => '2. Accounts and eligibility',
                'body' => 'You must be at least 16 years old. Give accurate information, keep your password to yourself, and use one account per person. You are responsible for what happens through your account. Tell us promptly if you believe someone else has access to it.',
            ],
            [
                'heading' => '3. If you are a candidate',
                'body' => 'Keep your profile and resume accurate and up to date, and only apply to jobs you genuinely intend to be considered for. When you apply, your application - including the resume and cover letter you attached - is sent to that employer. You are responsible for the content you upload and for having the right to share it.',
            ],
            [
                'heading' => '4. If you are a recruiter or company',
                'body' => 'You must be authorised to publish jobs on behalf of the company you represent. Job postings must be truthful, lawful and free of discrimination, must describe a real role, must not ask candidates for money or fees, and must comply with employment law in the place where the role is based. You are responsible for how you handle the candidate data you receive.',
            ],
            [
                'heading' => '5. Acceptable use',
                'body' => 'Do not scrape or bulk-download the platform, do not bypass the published rate limits, do not send spam or unsolicited marketing, do not impersonate anyone, do not upload malicious code, do not attempt to reach data you are not authorised to see, and do not interfere with the availability of the service for others.',
            ],
            [
                'heading' => '6. Your content',
                'body' => 'You keep ownership of everything you upload. You grant us the licence we need to host, store, display and deliver it in order to run the service - for example, showing a job posting publicly or passing an application to the employer it was sent to.',
            ],
            [
                'heading' => '7. Applications and hiring decisions',
                'body' => 'Recruitivo is not the employer and takes no part in hiring decisions. We do not verify employers, job postings or candidate claims. The relationship, the process and any offer are between the candidate and the employer.',
            ],
            [
                'heading' => '8. The demo environment',
                'body' => 'demo.recruivo.work is a public demonstration filled with fictional people, companies and jobs. Its accounts are read-only and its data is reset periodically, so anything you enter there can disappear. Do not enter real personal data in the demo.',
            ],
            [
                'heading' => '9. Availability and changes to the service',
                'body' => 'We aim to keep the platform available and reliable, but we do not promise uninterrupted service. Features may be added, changed or withdrawn, and the service may be interrupted for maintenance.',
            ],
            [
                'heading' => '10. Suspension and termination',
                'body' => 'We may suspend or close an account that breaches these terms, that puts other users or the platform at risk, or that we are required to act on. You can close your account at any time from your profile settings.',
            ],
            [
                'heading' => '11. Disclaimers and liability',
                'body' => 'The platform is provided as it is. To the extent the law allows, we are not liable for indirect or consequential losses, for the content or conduct of other users, or for hiring outcomes. Nothing in these terms limits liability that cannot be limited by law.',
            ],
            [
                'heading' => '12. Changes to these terms',
                'body' => 'We may update these terms as the platform changes. The date at the top of this page reflects the current version, and continuing to use the service after an update means you accept it.',
            ],
        ],
    ],
];
