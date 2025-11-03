# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

***

## 4.1.0
Release date: Jul 11th, 2025

### Added
+ PLGDC8-85: Drupal release action
+ DAVAMS-851: Add BILLINK
+ DAVAMS-830: Add Bizum

### Removed
+ DAVAMS-894: Remove ALIPAY payment method

### Changed
+ PLGDC8-82: Remove iDEAL issuers

***

## 4.0.0
Release date: Sep 13th, 2024

### Added
+ PLGDC8-76: Drupal 10 support

### Removed
+ PLGDC8-75: Drop support for versions between 9.0.0 and 9.4.2 because of security issues

***

## 3.1.0
Release date: Mar 22nd, 2023

### Added
+ PLGDC8-60 / PLGDC8-59: Add support for canceled order status
+ PLGDC8-64: Add logging function on onNotify function

### Fixed
+ Fix empty profile support - Issue [#3221601](https://www.drupal.org/project/commerce_multisafepay_payments/issues/3221601) by [Mykola Dolynskyi](https://www.drupal.org/u/mykola-dolynskyi)

***

## 3.0.0
Release date: Mar 19th, 2021

### Added
+ DAVAMS-272: Add CBC payment method
+ PLGDC8-48: Add generic gateway
+ DAVAMS-336: Add Good4fun Giftcard

### Changed
+ DAVAMS-317: Rebrand Klarna
+ DAVAMS-300: Rebrand Direct Bank Transfer to Request to Pay
+ Switch to semantic versioning

***

## 8.x-2.0
Release date: Jun 24th, 2020

### Added
+ PLGDC8-41: Add Commerce Log dependency
+ PLGDC8-42: Add core_version_requirement for Drupal 8.8 or higher and Drupal 9 compatibility

### Fixed
+ PLGDC8-40: Fix undefined function system_get_info
+ PLGDC8-37: Fix order is completed twice - Issue #3136434 by j3ll3nl
+ Fix multisafepay_orderdata hook - by kevinvhengst
+ PLGDC8-34: Fix undefined index by only hooking into checkout forms - Issue #3125598 by corneboele
+ PLGDC8-27: Fix API object compatibility warning with PHP 7 - Issue #3117427 by corneboele, j3ll3nl

### Changed
+ DAVAMS-225: Rename Santander Betaalplan to Santander Betaal per Maand

***

## 8.x-1.1
Release date: Apr 1st, 2020

### Added
+ PLGDC8-31: Add Apple Pay
+ PLGDC8-30: Add Direct Bank Transfer
+ PLGDC8-28: Add MultiSafepay order data hooks - Issue #3117420 by j3ll3nl

### Changed
+ PLGDC8-26: Refactor the way to get config data - Issue #3117628 by j3ll3nl
+ PLGDC8-29: Use orderNumber if exist for transaction request - Issue #3117942 by j3ll3nl

***

## 8.x-1.0
Release date: Dec 4th, 2019

Initial release
