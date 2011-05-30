Static Section
==============

Static Sections simplify the process of editing field collections that should only exist once in Symphony.

- Version: 1.6.1
- Author: [Nathan Martin](mailto:nathan@knupska.com), [Rainer Borene](mailto:rainerborene@gmail.com), [Vlad Ghita](mailto:vlad.ghita@xandergroup.ro)
- Build Date: 2011-01-11
- Requirements: Symphony 2.2.1

### Installation

1. Upload the 'static_section' folder found in this archive to your Symphony 'extensions' folder.

2. Enable it by selecting the "Static Section" extension, choose Enable from the 'With Selected' dropdown menu, then click Apply.

3. Bluprints > Sections and edit some section that you'll "Make this section static (i.e a single entry section)".

### Important Notes

1. When adding to a previously created section, please ensure that the section contains only one entry.  
   If added to a section that contains multiple entries, only the first entry in that section will be  
   available to edit until the "Static Section" flag is removed from the section

2. This extension does not modify the section data source output.  
   As such if there are additional entries (more than one) in the section, these additional entries  
   will still be output by Symphony when selecting the section as a data source.

## Compatibility

Symphony    | Static Section
  ------------| -------------
  2.0 – 2.0.5 | Not compatible
  2.0.6 – 2.2 | [1.5](https://github.com/knupska/static_section/tree/1.5)
  2.2.*       | [latest](https://github.com/knupska/static_section/tree/1.6.1)

## Changelog

**1.6.1**

- when static section, `<h2>` element from `publish/edit` and `publish/new` page contains section name
- refactored the code

**1.6**

- rewrite for Symphony 2.2.1 new delegates
