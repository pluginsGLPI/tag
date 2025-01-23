# Change Log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [UNRELEASED]

### Fixed

- Apply `tag`  based on rules in all cases (for example: the user is self-service)

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
