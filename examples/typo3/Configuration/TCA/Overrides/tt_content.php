<?php
// Configuration/TCA/Overrides/tt_content.php
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    [
        'LLL:EXT:your_extension/Resources/Private/Language/locallang.xlf:ai_generator',
        'ai_generator',
        'EXT:your_extension/Resources/Public/Icons/ai.svg'
    ],
    'CType',
    'your_extension'
);
