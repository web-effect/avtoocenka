<?php
namespace App\Services;

class UserService
{
    public const REQUIRED_EXT_FIELDS = [
        'hired' => [
            // Сведения о юридическом лице
            'company_inn_ogrn',
            'company_director_name',
            'company_director_position',
            'company_legal_address',
            //'company_actual_address',
            'company_phone',
            'company_email',
            // Страхование ответственности юридического лица
            'company_insurer_name',
            'company_insurance_policy_number',
            'company_insurance_contract_date',
            'company_insurance_scan',
            'company_insurance_amount',
            'company_insurance_valid_from',
            'company_insurance_valid_to',
            // Сведения об оценщике
            'appraiser_name',
            'appraiser_sro',
            'appraiser_sro_registry_number',
            'appraiser_sro_certificate_number',
            'appraiser_sro_certificate_date',
            'appraiser_sro_certificate_scan',
            // Документы о профессиональных знаниях
            'education_institution_name',
            'diploma_series_number',
            'diploma_registration_number',
            'diploma_scan',
            'qualification_certificate_number',
            'qualification_certificate_scan',
            'qualification_certificate_date',
            'appraisal_experience',
            // Страхование ответственности оценщика
            'appraiser_insurer_name',
            'appraiser_insurance_policy_number',
            'appraiser_insurance_contract_date',
            'appraiser_insurance_scan',
            'appraiser_insurance_amount',
            'appraiser_insurance_valid_from',
            'appraiser_insurance_valid_to',
        ],
        'private' => [
            // Сведения о ЧПО
            'appraiser_name',
            'appraiser_inn',
            'appraiser_address',
            'appraiser_phone',
            'appraiser_email',
            'appraiser_sro',
            'appraiser_sro_registry_number',
            'appraiser_sro_certificate_number',
            'appraiser_sro_certificate_date',
            'appraiser_sro_certificate_scan',
            // Документы о профессиональных знаниях
            'education_institution_name',
            'diploma_series_number',
            'diploma_registration_number',
            'diploma_scan',
            'qualification_certificate_number',
            'qualification_certificate_scan',
            'qualification_certificate_date',
            'appraisal_experience',
            // Страхование ответственности оценщика
            'appraiser_insurer_name',
            'appraiser_insurance_policy_number',
            'appraiser_insurance_contract_date',
            'appraiser_insurance_scan',
            'appraiser_insurance_amount',
            'appraiser_insurance_valid_from',
            'appraiser_insurance_valid_to',
        ],
    ];



    public static function id(): int
    {
        global $modx;
        if (!$modx->user->isAuthenticated('web')) return 0;
        return (int)($_SESSION['modx.user.contextTokens']['web'] ?? 0);
    }

    public static function isAdmin(): bool
    {
        // админ — тот кто залогинен в админку
        global $modx;
        return self::id() && $modx->user->isAuthenticated('mgr');
    }

    // запрос прав доступа
    public static function requireAccess(string $role = ''): void
    {
        if (!self::id()) {
            abortx(401);
        }

        if ($role == 'admin' && !self::isAdmin()) {
            abortx(403);
        }
    }

    public static function getAvailableReports(): int
    {
        if (!self::id()) return 0;
        $profile = self::getProfile();
        return (int)($profile['available_reports'] ?? 0);
    }

    public static function changeAvailableReports(int $count, int $userId = 0): void
    {
        global $modx;
        
        if (!$userId && !self::id()) return;

        if (!$userId || $userId === self::id()) {
            $user = $modx->user;
        } else {
            $user = $modx->getObject('modUser', $userId);
        }

        if (!$user) return;
        $profile = $user->getOne('Profile');
        if (!$profile) return;

        $profile->set('fax', (int)($profile->fax ?: 0) + $count);
        $profile->save();
    }

    public static function getProfile(int $userId = 0): ?array
    {
        global $modx;

        if (!$userId && !self::id()) return null;

        if (!$userId || $userId === self::id()) {
            $user = $modx->user;
        } else {
            $user = $modx->getObject('modUser', $userId);
        }

        if (!$user) return null;
        $profile = $user->getOne('Profile');
        if (!$profile) return null;

        $ext = $profile->get('extended') ?: [];

        $missingFields = UserService::getMissingProfileFields($profile);

        $data = [
            'id' => $user->id,
            'phone' => $profile->phone ?: '',
            'email' => $profile->email ?: '',
            'fullname' => $profile->fullname ?: '',
            'is_complete' => empty($missingFields),
            'missing_fields' => $missingFields,
            'available_reports' => (int)($profile->fax ?: 0),
        ];

        $data = array_merge($ext, $data);

        $data['appraiser_type'] = 'private'; // сейсас только частнопрактикующий

        return array_merge($ext, $data);
    }

    public static function getMissingProfileFields(object $profile): array
    {
        $extended = $profile->get('extended') ?: [];

        $type = $extended['appraiser_type'] ?? null;
        if (!$type || empty(self::REQUIRED_EXT_FIELDS[$type])) {
            return ['appraiser_type'];
        }

        $missing = [];

        foreach (self::REQUIRED_EXT_FIELDS[$type] as $field) {
            if (empty($extended[$field])) {
                $missing[] = $field;
            }
        }

        return $missing;
    }

}