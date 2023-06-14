[![TYPO3 extension tvplus_contentslide](https://shields.io/endpoint?label=EXT&url=https://typo3-badges.dev/badge/tvplus_contentslide/extension/shields)](https://extensions.typo3.org/extension/tvplus_contentslide)
[![Latest TER version](https://shields.io/endpoint?label=TER&url=https://typo3-badges.dev/badge/tvplus_contentslide/version/shields)](https://extensions.typo3.org/extension/tvplus_contentslide)
[![Latest Packagist version](https://shields.io/packagist/v/jpmschuler/tvplus-contentslide?label=Packagist&logo=packagist&logoColor=white)](https://packagist.org/packages/jpmschuler/tvplus-contentslide)
![Total downloads](https://typo3-badges.dev/badge/tvplus_contentslide/downloads/shields.svg)

![Supported TYPO3 versions](https://shields.io/endpoint?label=typo3&url=https://typo3-badges.dev/badge/tvplus_contentslide/typo3/shields)
![Supported PHP versions](https://shields.io/packagist/php-v/jpmschuler/tvplus-contentslide?logo=php)
[![Current CI health](https://github.com/jpmschuler/TYPO3-tvplus-contentslide/actions/workflows/ci.yml/badge.svg)](https://github.com/jpmschuler/TYPO3-tvplus-contentslide/actions/workflows/ci.yml)

# EXT:tvplus_contentslide
This extension allows you to inherit the content of a TemplaVoilaPlus content element column to its child pages - Adaption of EXT:kb_tv_cont_slide to work with templavoilaplus

# Compatibility
As this extension has a quite limited set of features, there will only be seldom updates.
The current version `v11.0.6` is basically compatible with
- TYPO3: 9LTS, 10LTS, 11LTS
- PHP: ^7.3 || ^8.0
- EXT:templavoilaplus: v7 || v8

# Installation
Either install `EXT:tvplus_contentslide` via TER (Extension Manager) or via composer `jpmschuler/tvplus-contentslide`

# How to use

Inside your TypoScript, instead of
```
lib.sidebarContent = RECORDS
lib.sidebarContent.source.current = 1
lib.sidebarContent.tables = tt_content
```

use the following TypoScript
```
lib.sidebarContent = RECORDS
lib.sidebarContent.source.postUserFunc = Jpmschuler\TvplusContentslide\SlideController->main
lib.sidebarContent.source.postUserFunc.field = field_sidebar
lib.sidebarContent.tables = tt_content
```
