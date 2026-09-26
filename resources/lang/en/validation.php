<?php

return [
    // Profile validation
    'email_taken' => 'This email address is already taken.',
    'email_invalid' => 'Please provide a valid email address.',
    'name_max' => 'The name may not be greater than :max characters.',
    'location_max' => 'The location may not be greater than :max characters.',
    'phone_max' => 'The phone number may not be greater than :max characters.',
    'profile_summary_max' => 'The profile summary may not be greater than :max characters.',

    // Company validation
    'company_name_max' => 'The company name may not be greater than :max characters.',
    'company_name_required' => 'Please tell us the company name so candidates can recognise your brand.',
    'tagline_max' => 'The tagline may not be greater than :max characters.',
    'website_url_invalid' => 'Please provide a valid website URL.',
    'linkedin_url_invalid' => 'Please provide a valid LinkedIn URL.',
    'company_size_max' => 'The company size may not be greater than :max characters.',
    'founded_year_invalid' => 'The founded year must be a valid year.',
    'founded_year_min' => 'The founded year must be after :min.',
    'founded_year_future' => 'The founded year cannot be in the future.',

    // Application validation
    'notes_required_status' => 'Please include a note when accepting or rejecting an application.',
    'status_invalid' => 'Status must be either pending, shortlisted, interview, accepted, or rejected.',
    'interview_at_required' => 'Please choose an interview date and time.',
    'interview_at_after' => 'The interview must be scheduled in the future.',
    'interview_location_required' => 'Please provide an interview location.',
    'interview_url_required' => 'Please provide a meeting link (http or https).',
    'interview_url_invalid' => 'Please provide a valid meeting link (http or https).',
    'interview_mode_invalid' => 'Please choose whether the interview is online or on-site.',
    'status_withdrawn_not_allowed' => 'Withdrawn can only be set by the candidate.',
    'closes_at_after_or_equal' => 'The closing date must be today or later.',
    'closes_at_date' => 'Please provide a valid closing date.',

    // Password validation
    'current_password_required' => 'Current password is required.',
    'password_required' => 'New password is required.',
    'password_mismatch' => 'Password confirmation does not match.',
    'password_confirmation_required' => 'Password confirmation is required.',
    'password_length' => 'The :attribute must be between 12 and 64 characters.',
    'password_no_username' => 'The :attribute must not contain your username.',
    'password_complexity' => 'The :attribute must include at least 3 of the following: uppercase letters, lowercase letters, numbers, and symbols.',
];
