<?php

namespace App\Enums;

enum ItCategory: string
{
    case SoftwareDevelopment = 'Software Development';
    case CloudComputing = 'Cloud Computing';
    case Cybersecurity = 'Cybersecurity';
    case DataAnalytics = 'Data Analytics';
    case ArtificialIntelligence = 'Artificial Intelligence';
    case DevOps = 'DevOps';
    case WebDevelopment = 'Web Development';
    case FullStackDevelopment = 'Full-Stack Development';
    case Networking = 'Networking';
    case InformationSecurity = 'Information Security';
    case HardwareSystems = 'Hardware Systems';
    case IoT = 'IoT Systems';
    case QuantumComputing = 'Quantum Computing';
    case InteractiveSoftware = 'Interactive Software';
    case ItConsulting = 'IT Consulting';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
