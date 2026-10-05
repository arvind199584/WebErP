<?php
declare(strict_types=1);
namespace App\Modules\AI_and_Tools\AIDrafter\Services;

class PromptService {
    /**
     * Orchestrates the final prompt by applying government drafting rules.
     */
    public function buildStructuredPrompt(array $params): string {
        $recipient = $params['recipient'] ?? null;
        $agreement = $params['agreement'] ?? null;
        $agency = $params['agency'] ?? null;
        $type = $params['type'] ?? 'General';
        $context = $params['context'] ?? '';
        $officeName = $params['office_name'] ?? 'DDA Sports Complex';

        // 1. SYSTEM ROLE & CORE RULES
        $prompt = "You are an expert Executive Assistant at the Delhi Development Authority (DDA). ";
        $prompt .= "Your task is to draft a formal government letter following standard Indian Government (Central Secretariat) Manual of Office Procedure. ";

        $prompt .= "\n\nSTRICT RULES:";
        $prompt .= "\n- Use a highly professional, formal, and authoritative tone.";
        $prompt .= "\n- Start with a clear 'Subject:' line.";
        $prompt .= "\n- Use 'Sir/Madam,' as the salutation.";
        $prompt .= "\n- If an agreement is referenced, start the first paragraph with 'With reference to the agreement cited above...'";
        $prompt .= "\n- Use 'Yours faithfully,' for the sign-off.";
        $prompt .= "\n- Do not use flowery language; be direct and precise.";
        $prompt .= "\n- Ensure the letterhead information is correctly placed at the top.";

        // 2. RECIPIENT CONTEXT
        if ($recipient) {
            $prompt .= "\n\nRECIPIENT DETAILS:";
            $prompt .= "\nName: {$recipient['name']}";
            $prompt .= "\nDesignation: {$recipient['designation']}";
            $prompt .= "\nDepartment: {$recipient['department']}";
            $prompt .= "\nAddress: {$recipient['address']}";
        }

        // 3. AGREEMENT CONTEXT
        if ($agreement && $agency) {
            $prompt .= "\n\nAGREEMENT CONTEXT:";
            $prompt .= "\nAgreement No: {$agreement->agreement_no}";
            $prompt .= "\nAgency Name: {$agency->name}";
            $prompt .= "\nTendered Amount: Rs. {$agreement->tendered_amount}";
            $prompt .= "\nStart Date: {$agreement->period_from}";
        }

        // 4. USER INTENT & CUSTOM CONTEXT
        $prompt .= "\n\nLETTER INTENT:";
        $prompt .= "\nSubject Type: $type";
        $prompt .= "\nSpecific Instructions from User: $context";

        // 5. OFFICE FOOTER
        $prompt .= "\n\nSENDER:";
        $prompt .= "\nOffice: $officeName";

        return $prompt;
    }
}
