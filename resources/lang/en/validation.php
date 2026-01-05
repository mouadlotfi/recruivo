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
    'status_invalid' => 'Status must be either pending, accepted, or rejected.',
    
    // Password validation
    'current_password_required' => 'Current password is required.',
    'password_required' => 'New password is required.',
    'password_mismatch' => 'Password confirmation does not match.',
    'password_confirmation_required' => 'Password confirmation is required.',
    'password_length' => 'The :attribute must be between 12 and 64 characters.',
    'password_no_username' => 'The :attribute must not contain your username.',
    'password_complexity' => 'The :attribute must include at least 3 of the following: uppercase letters, lowercase letters, numbers, and symbols.',
];
