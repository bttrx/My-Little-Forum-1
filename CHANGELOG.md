# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## 2025-11-09

### Added

- Support for `[sub][/sub]` and `[sup][/sup]` BBCode tags.

## 2025-10-27

### Added

- Forum setting 'Users online counter' can be set to count registered users only.  
  See [Users online counter](https://www.bttr-software.de/forum/forum_entry.php?id=22862).
- New language string `counter_uo_ng` in `lang/english.php`.
- New language strings `count_only_reg_users` and `count_all_users` in `lang/english_add.php`.

## 2025-06-09

### Fixed

- Database error when using certain characters, e.g., Cyrillic О or Cyrillic А.  
  See ['Full' Unicode support](https://www.bttr-software.de/forum/forum_entry.php?id=22653).

## 2025-05-10

### Changed

- Update copyright year in `README`.

### Fixed

- RSS feed items were always delivered with `GMT` instead of correct time zone offset.  
  See [RSS feed timestamps (aka pubDate)](https://www.bttr-software.de/forum/forum_entry.php?id=22573).

## 2024-02-06

### Changed

- Notification mails:
  - Replaced global default view setting with user's default view setting in forum link generation.
  - 'From:' field format changed from 'Forum name' to 'Sender via Forum name'.
- Update copyright year in `README`.

## 2022-03-31

### Added

- `CHANGELOG.md` (this file).
- Check length and content of sender name in contact form to reduce number of spam messages.
- New language strings `valid_chars_in_name` and `invalid_char_combination` in `lang/english.php`.

### Changed

- Update copyright year in `README`.

