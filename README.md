[![CI](https://github.com/jpmschuler/TYPO3-tvplus-contentslide/actions/workflows/ci.yml/badge.svg)](https://github.com/jpmschuler/TYPO3-tvplus-contentslide/actions/workflows/ci.yml)
[![Release](https://img.shields.io/github/v/release/jpmschuler/TYPO3-tvplus-contentslide.svg)](https://github.com/jpmschuler/TYPO3-tvplus-contentslide/actions/workflows/ci.yml)


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
