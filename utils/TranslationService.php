<?php
// utils/TranslationService.php

class TranslationService {
    
    // Translation dictionary for common administrative agenda phrases
    private $dictionary = [
        'mr' => [
            'weekly planning meeting' => 'साप्ताहिक नियोजन बैठक',
            'weekly planning' => 'साप्ताहिक नियोजन',
            'review weekly goals and pending tasks.' => 'साप्ताहिक उद्दिष्टे आणि प्रलंबित कामांचा आढावा घेणे.',
            'discuss hr policy updates and approvals.' => 'मानव संसाधन धोरण अद्यतने आणि मंजुरींवर चर्चा करा.',
            'hr policy review' => 'मानव संसाधन धोरण पुनरावलोकन',
            'prepare agenda document' => 'नियोजन दस्तऐवज तयार करा',
            'send attendance reminder' => 'उपस्थिती स्मरणपत्र पाठवा',
            'update policy checklist' => 'धोरण तपासणी सूची अद्ययावत करा',
            'district planning meeting' => 'जिल्हा नियोजन बैठक',
            'district review meeting' => 'जिल्हा आढावा बैठक',
            'offline' => 'ऑफलाईन (प्रत्यक्ष)',
            'online' => 'ऑनलाईन (दूरदृश्य प्रणाली)',
            'hybrid' => 'हायब्रिड',
            'scheduled' => 'नियोजित',
            'pending' => 'प्रलंबित',
            'in progress' => 'प्रगतीपथावर',
            'completed' => 'पूर्ण',
            'cancelled' => 'रद्द'
        ]
    ];

    /**
     * Translates a given text from source language to target language.
     * 
     * @param string $text The text to translate.
     * @param string $targetLang The target language code (e.g. 'mr', 'en').
     * @param string $sourceLang The source language code (default 'en').
     * @return string Translated text.
     */
    public function translateText($text, $targetLang, $sourceLang = 'en') {
        $text = trim($text);
        if (empty($text)) {
            return '';
        }

        // Normalize target language code
        $targetLang = strtolower($targetLang);
        $sourceLang = strtolower($sourceLang);

        if ($targetLang === $sourceLang) {
            return $text;
        }

        $lowerText = strtolower($text);

        // Check if exact match exists in dictionary
        if (isset($this->dictionary[$targetLang][$lowerText])) {
            return $this->dictionary[$targetLang][$lowerText];
        }

        // Try translating substrings or sentences if it's longer
        if (isset($this->dictionary[$targetLang])) {
            foreach ($this->dictionary[$targetLang] as $enPhrase => $mrPhrase) {
                if (strpos($lowerText, $enPhrase) !== false) {
                    return $mrPhrase;
                }
            }
        }

        // Mock translation fallback (adds Marathi markers or suffixes to give a local context mock)
        if ($targetLang === 'mr') {
            return "[मराठी अनुवाद]: " . $text . " (भाषांतर उपलब्ध नाही)";
        }

        return $text;
    }
}
