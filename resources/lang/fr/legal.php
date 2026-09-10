<?php

/**
 * Documents juridiques, structurés en titre + sections afin qu'un même composant
 * affiche l'un ou l'autre et que les deux langues restent synchronisées. Les
 * faits décrits sont ceux que la plateforme implémente réellement (stockage privé
 * des CV, cookies fonctionnels uniquement, Google Fonts, limitation de débit,
 * réinitialisation de la démo).
 */
return [
    'updated_date' => '10 septembre 2026',
    'updated_label' => 'Dernière mise à jour : :date',
    'back_home' => 'Retour à l\'accueil',
    'questions' => 'Une question sur ce document ou une demande concernant vos données ? Écrivez à :email.',

    'privacy' => [
        'title' => 'Politique de confidentialité',
        'summary' => 'Quelles données personnelles Recruivo traite, pourquoi, qui peut les voir et ce que vous pouvez nous demander.',
        'sections' => [
            [
                'heading' => '1. Champ d\'application',
                'body' => 'Recruivo est une plateforme d\'emploi : les candidats trouvent des offres et postulent, les recruteurs publient des offres et gèrent les candidatures, et les administrateurs exploitent la plateforme. Cette politique décrit le traitement des données personnelles sur recruivo.work et sur la démo publique demo.recruivo.work. Elle concerne les visiteurs, les candidats, les recruteurs et les administrateurs.',
            ],
            [
                'heading' => '2. Données que vous nous fournissez',
                'body' => 'À la création d\'un compte : votre nom, votre adresse e-mail et un mot de passe (conservé uniquement sous forme de hachage). Si vous êtes candidat, nous conservons également ce que vous choisissez d\'ajouter à votre profil - téléphone, localisation, accroche, présentation, compétences, langues, liens, expériences, formations, catégories préférées et votre CV. Si vous représentez une entreprise, nous conservons les informations que vous fournissez : nom, slogan, localisation, taille, site web, page LinkedIn, mission, culture et logo.',
            ],
            [
                'heading' => '3. Données créées par l\'utilisation de la plateforme',
                'body' => 'Lorsque vous postulez, nous conservons la candidature, le CV joint, votre lettre de motivation, les changements de statut, les notes rédigées par le recruteur, les détails d\'entretien et les notifications qui vous sont envoyées. Les offres enregistrées et vos préférences rapides le sont également, afin de pouvoir vous les réafficher.',
            ],
            [
                'heading' => '4. Données techniques',
                'body' => 'Nous enregistrons l\'adresse IP et l\'agent utilisateur à l\'origine des requêtes, pour la sécurité et la limitation de débit. Nous déposons des cookies fonctionnels : un cookie de session, un jeton CSRF, et des cookies mémorisant votre langue et votre thème. Les journaux applicatifs enregistrent requêtes et erreurs pour le diagnostic ; ils sont soumis à rotation et conservés peu de temps.',
            ],
            [
                'heading' => '5. Utilisation de vos données',
                'body' => 'Pour faire fonctionner la plateforme : créer et sécuriser votre compte, publier les offres, transmettre vos candidatures au recruteur concerné, envoyer les e-mails transactionnels (vérification d\'adresse, réinitialisation de mot de passe, suivi de candidature), afficher vos notifications, prévenir les abus, diagnostiquer les pannes et maintenir le service disponible.',
            ],
            [
                'heading' => '6. Fondements de ces traitements',
                'body' => 'Nous traitons ces données pour exécuter le contrat que vous concluez en utilisant la plateforme, pour notre intérêt légitime à sécuriser et améliorer le service, pour respecter nos obligations légales et, lorsque la loi l\'exige, sur la base d\'un consentement que vous pouvez retirer à tout moment.',
            ],
            [
                'heading' => '7. Cookies et requêtes vers des tiers',
                'body' => 'Les cookies que nous déposons sont ceux dont le site a besoin pour fonctionner : un cookie de session, un jeton CSRF, des cookies mémorisant votre langue et votre thème, et un cookie indiquant que vous avez vu le bandeau d\'information. Nous n\'utilisons aucun cookie publicitaire ou de mesure d\'audience et aucun traceur tiers. Les polices sont servies par ce site et non par un tiers : rien de ce que vous chargez ici ne transmet votre adresse IP à quelqu\'un d\'autre. Le bandeau explique cela ; aucun élément du site n\'attend votre consentement, car tous les cookies ci-dessus sont nécessaires à son fonctionnement. Le site est diffusé via Cloudflare, qui voit nécessairement le trafic qu\'il relaie.',
            ],
            [
                'heading' => '8. Qui peut voir vos données',
                'body' => 'Les recruteurs voient les candidatures déposées sur leurs propres offres, y compris le CV et la lettre qui les accompagnent. Les administrateurs peuvent consulter les données de la plateforme dans le cadre de son exploitation. Nos prestataires d\'hébergement et d\'envoi d\'e-mails traitent les données sur nos instructions. Nous ne vendons aucune donnée personnelle et ne la partageons pas avec des annonceurs.',
            ],
            [
                'heading' => '9. Stockage et sécurité',
                'body' => 'Les données sont stockées sur des serveurs que nous exploitons. Les CV et autres fichiers déposés sont conservés dans un espace privé, accessible uniquement via des routes qui vérifient votre identité et vos autorisations. Le trafic est chiffré en HTTPS. Sessions, cache et files d\'attente utilisent un stockage distinct de la base de données. Aucun système n\'est parfait : nous limitons donc les données conservées à ce dont le service a réellement besoin.',
            ],
            [
                'heading' => '10. Durées de conservation',
                'body' => 'Les données de compte et de profil sont conservées tant que votre compte est actif, puis uniquement le temps exigé par la loi (comptabilité, litiges). La suppression de votre compte efface votre profil, vos candidatures, vos CV déposés et vos offres enregistrées. Les journaux et sauvegardes expirent selon leurs propres durées de rétention.',
            ],
            [
                'heading' => '11. Vos droits',
                'body' => 'Vous pouvez demander à consulter les données vous concernant, les corriger, les supprimer, en limiter ou en refuser l\'usage, les recevoir dans un format portable, ou retirer un consentement déjà donné. Vous pouvez supprimer votre compte vous-même depuis vos paramètres de profil, et nous écrire pour toute autre demande. Nous répondons dans les délais prévus par la loi applicable, et vous pouvez saisir l\'autorité de protection des données de votre pays.',
            ],
            [
                'heading' => '12. Mineurs',
                'body' => 'Recruivo ne s\'adresse pas aux enfants. Vous devez avoir au moins 16 ans pour créer un compte, et nous ne collectons pas sciemment de données concernant des personnes plus jeunes.',
            ],
            [
                'heading' => '13. Modifications de cette politique',
                'body' => 'Cette politique peut évoluer avec la plateforme. La date en haut de page correspond toujours à la version en vigueur, et les changements importants sont annoncés dans l\'application.',
            ],
        ],
    ],

    'terms' => [
        'title' => 'Conditions d\'utilisation',
        'summary' => 'Les règles applicables lorsque vous utilisez Recruivo en tant que visiteur, candidat ou recruteur.',
        'sections' => [
            [
                'heading' => '1. Acceptation des conditions',
                'body' => 'En utilisant Recruivo, en créant un compte ou en postulant à une offre, vous acceptez les présentes conditions. Si vous ne les acceptez pas, n\'utilisez pas la plateforme.',
            ],
            [
                'heading' => '2. Comptes et conditions d\'accès',
                'body' => 'Vous devez avoir au moins 16 ans. Fournissez des informations exactes, gardez votre mot de passe confidentiel et utilisez un seul compte par personne. Vous êtes responsable de ce qui se passe via votre compte. Prévenez-nous rapidement si vous pensez qu\'un tiers y a accès.',
            ],
            [
                'heading' => '3. Si vous êtes candidat',
                'body' => 'Maintenez votre profil et votre CV exacts et à jour, et ne postulez qu\'aux offres pour lesquelles vous souhaitez réellement être considéré. Lorsque vous postulez, votre candidature - CV et lettre de motivation compris - est transmise à cet employeur. Vous êtes responsable des contenus que vous déposez et du droit de les partager.',
            ],
            [
                'heading' => '4. Si vous êtes recruteur ou entreprise',
                'body' => 'Vous devez être autorisé à publier des offres pour l\'entreprise que vous représentez. Les offres doivent être sincères, licites, non discriminatoires, décrire un poste réel, ne demander aucun paiement aux candidats et respecter le droit du travail du lieu où le poste est basé. Vous êtes responsable du traitement des données de candidats que vous recevez.',
            ],
            [
                'heading' => '5. Utilisation acceptable',
                'body' => 'N\'extrayez pas massivement le contenu de la plateforme, ne contournez pas les limites de débit publiées, n\'envoyez ni spam ni prospection non sollicitée, n\'usurpez pas l\'identité d\'autrui, ne déposez pas de code malveillant, ne tentez pas d\'accéder à des données non autorisées et ne nuisez pas à la disponibilité du service pour les autres.',
            ],
            [
                'heading' => '6. Vos contenus',
                'body' => 'Vous restez propriétaire de tout ce que vous déposez. Vous nous accordez la licence nécessaire pour héberger, stocker, afficher et transmettre ces contenus afin d\'exploiter le service - par exemple afficher publiquement une offre ou transmettre une candidature à l\'employeur concerné.',
            ],
            [
                'heading' => '7. Candidatures et décisions de recrutement',
                'body' => 'Recruivo n\'est pas l\'employeur et ne participe à aucune décision de recrutement. Nous ne vérifions ni les employeurs, ni les offres, ni les déclarations des candidats. La relation, le processus et toute proposition concernent le candidat et l\'employeur.',
            ],
            [
                'heading' => '8. Environnement de démonstration',
                'body' => 'demo.recruivo.work est une démonstration publique peuplée de personnes, d\'entreprises et d\'offres fictives. Ses comptes sont en lecture seule et ses données sont réinitialisées périodiquement : ce que vous y saisissez peut disparaître. N\'y saisissez aucune donnée personnelle réelle.',
            ],
            [
                'heading' => '9. Disponibilité et évolution du service',
                'body' => 'Nous visons une plateforme disponible et fiable, sans promettre un service ininterrompu. Des fonctionnalités peuvent être ajoutées, modifiées ou retirées, et le service peut être interrompu pour maintenance.',
            ],
            [
                'heading' => '10. Suspension et résiliation',
                'body' => 'Nous pouvons suspendre ou fermer un compte qui enfreint ces conditions, qui met en danger les autres utilisateurs ou la plateforme, ou lorsque la loi nous y oblige. Vous pouvez fermer votre compte à tout moment depuis vos paramètres de profil.',
            ],
            [
                'heading' => '11. Garanties et responsabilité',
                'body' => 'La plateforme est fournie en l\'état. Dans les limites permises par la loi, nous ne sommes pas responsables des dommages indirects, des contenus ou comportements d\'autres utilisateurs, ni des résultats de recrutement. Aucune clause ne limite une responsabilité qui ne peut l\'être légalement.',
            ],
            [
                'heading' => '12. Modifications de ces conditions',
                'body' => 'Ces conditions peuvent évoluer avec la plateforme. La date en haut de page reflète la version en vigueur ; continuer à utiliser le service après une mise à jour vaut acceptation.',
            ],
        ],
    ],
];
