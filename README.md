# EXT:tvplus_contentslide
This extension allows you to inherit the content of a TemplaVoilaPlus content element column to its child pages - Adaption of EXT:kb_tv_cont_slide to work with templavoilaplus

# Installation
Either install `EXT:tvplus_contentslide via TER (Extension Manager) or via composer `jpmschuler/tvplus-contentslide`

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
