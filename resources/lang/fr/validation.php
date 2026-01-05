<?php

return [
    // Profile validation
    'email_taken' => 'Cette adresse e-mail est déjà utilisée.',
    'email_invalid' => 'Veuillez fournir une adresse e-mail valide.',
    'name_max' => 'Le nom ne peut pas dépasser :max caractères.',
    'location_max' => 'La localisation ne peut pas dépasser :max caractères.',
    'phone_max' => 'Le numéro de téléphone ne peut pas dépasser :max caractères.',
    'profile_summary_max' => 'Le résumé du profil ne peut pas dépasser :max caractères.',
    
    // Company validation
    'company_name_max' => 'Le nom de l\'entreprise ne peut pas dépasser :max caractères.',
    'company_name_required' => 'Veuillez nous indiquer le nom de l\'entreprise afin que les candidats puissent reconnaître votre marque.',
    'tagline_max' => 'Le slogan ne peut pas dépasser :max caractères.',
    'website_url_invalid' => 'Veuillez fournir une URL de site web valide.',
    'linkedin_url_invalid' => 'Veuillez fournir une URL LinkedIn valide.',
    'company_size_max' => 'La taille de l\'entreprise ne peut pas dépasser :max caractères.',
    'founded_year_invalid' => 'L\'année de fondation doit être une année valide.',
    'founded_year_min' => 'L\'année de fondation doit être après :min.',
    'founded_year_future' => 'L\'année de fondation ne peut pas être dans le futur.',
    
    // Application validation
    'notes_required_status' => 'Veuillez inclure une note lors de l\'acceptation ou du rejet d\'une candidature.',
    'status_invalid' => 'Le statut doit être en attente, accepté ou rejeté.',
    
    // Password validation
    'current_password_required' => 'Le mot de passe actuel est requis.',
    'password_required' => 'Le nouveau mot de passe est requis.',
    'password_mismatch' => 'La confirmation du mot de passe ne correspond pas.',
    'password_confirmation_required' => 'La confirmation du mot de passe est requise.',
    'password_length' => 'Le :attribute doit contenir entre 12 et 64 caractères.',
    'password_no_username' => 'Le :attribute ne doit pas contenir votre nom d\'utilisateur.',
    'password_complexity' => 'Le :attribute doit inclure au moins 3 des éléments suivants : lettres majuscules, lettres minuscules, chiffres et symboles.',
];
