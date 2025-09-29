# Change Log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [2.12.5] - 2025-09-29

### Fixed

- Fix the addition of tag via rules when users in different time zones are notified during ticket creation (#273)

## [2.12.4] - 2025-08-22

### Fixed

- Prevents FormCreator from deleting tags added via rules

## [2.12.3] - 2025-06-17

### Fixed

- Fix the addition of tag from a rule when updating actors only
- Fix the addition of multiple tags via rules

## [2.12.2] - 2025-02-19

### Fixed

- Apply `tag`  based on rules in all cases (for example: the user is self-service)
- Preventing the addition of tags to objects without creation rights
- See tag only on active itemtypes

## [2.12.1] - 2024-10-24

### Fixed

- Fix error message in search : "Unknown column 'is_active'" (#207)

## [2.12.0] - 2024-10-16

### Added

- Enable/disable a tag (#204)
- Allow to modify tag when itil object is closed

### Fixed

- Fix tag from cron

## [2.6.0]

### Added

- Show tags on Kanban view.

## [2.5.0]

### Added

- Add right management - Please review plugin rights after update.


## [0.90-1.1] - 2016

### Added

- First version only for 0.90 : this version check version 0.90 on install
- Important fix for datainjection : can use Tag and datainjection in the same GLPI
- Add support of datainjection : Can import Tags, with the fields : name, color in format #hex (#aaaaaa)
- Filter tags by type menu -> filter tags by itemtype(s)
- Few fixes foe 0.90
