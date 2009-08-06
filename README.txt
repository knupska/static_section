Static Section Field
------------------------------------

Version: 1.1
Author: Nathan Martin (nathan@knupska.com)
Build Date: 2009-08-06
Requirements: Symphony 2.0.6
			  (Very likely compatible with earlier versions, only tested in 2.0.6)

[INSTALLATION]
1. Upload the 'static_section' folder found in this archive to your Symphony 'extensions' folder.
   (If you have retrieved the extension via the download button in github you will have to
   rename the folder to 'static_section' as it will be called 'knupska-static_section-...')

2. Enable it by selecting the "Static Section" extension, choose Enable from the 'With Selected' dropdown menu, then click Apply.

3. You can now add the "Static Section" field to your sections.

[USAGE]

1. Add the "Static Section" field to your section.

2. Save your section.

[IMPORTANT NOTES]

1. When adding to a previously created section, please ensure that the section contains only one entry.
   If added to a section that contains multiple entries, only the first entry in that section will be
   available to edit until the "Static Section" field is removed from the section.
   
2. This extension does not modify the section data source output.
   As such if there are additional entries (more than one) in the section, these additional entries
   will still be output by Symphony when selecting the section as a data source.
