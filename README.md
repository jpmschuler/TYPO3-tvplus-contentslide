# How to use

Instead of 
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
