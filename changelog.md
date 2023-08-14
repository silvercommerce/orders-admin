# Log of changes for Orders Admin module

## 1.0.0

* First initial release

## 1.0.1

* Add locked check to estimate

## 1.0.2

* Switch to defaulting to the last edited date for StartDate (if null)

## 1.1.0

* Switch to config based rounding.
* Change Estimate/Invoice numbers to be incremental.
* Remove default Estimate/Invoice prefix if none set.

## 1.1.1

* Fix error with trimming estimate/invoice numbers 

## 1.1.2

* Fix error with trimming estimate/invoice numbers.

## 1.2.0

* Split estimate/invoice numbers to use seperate reference and prefix.
* Add migration tool to estimates/invoices to new ref/prefix config.

## 1.2.1

* Replace references to Number with FullRef
* Remove legact reports (replaced by reports module)

## 1.2.2

* Ensure country selection is using correct case
* Add extra methods to get uppercase country codes

## 1.2.3

* updated inline with discounts module

## 1.2.4

* Use ModelAdminPlus for admin area
* Add more complex date filter

## 1.2.5

* Fix bug generating line item key for a customisation

## 1.2.6

* Add requirement for has one button field

## 1.2.7

* Fixed invoice email postage

## 1.2.8

* Fixed error with HasOneButtonField when set to read-only

## 1.2.9

* changed Estimate summary fields

## 1.2.10

* Fix summary issue

## 1.2.11

* improved styling on invoice print view

## 1.2.12

* improved functionality of NumberMigration Task

## 1.2.13

* added config for replacing pdf css

## 1.2.14

* Update hasonefield

## 1.2.15

* Fix logo image in PDF
* Improve default PDF styling a little

## 1.2.16

* Fix logo image in PDF
* Improve default PDF styling a little

## 1.2.17

* Make Estimate/Invoice PDf CSS public dir aware

## 1.2.18

* Additional invoice PDF CSS tweaks

## 1.3.0

* Added factories for creating Estimates/Invoices
* Increase decimal sizes of base prices items
* Switch to "BasePrice" for line item prices
* Switch to using `Taxable` to calculate Tax Prices
* Fix travis support

## 1.3.1

* Switch to using BasePrice for customisation and correctly calculate tax

## 1.3.2

* Use new price fields for invoice/email templates

## 1.3.3

* Add ability to set the customer for an order via the `OrderFactory`
* Update AddLineItem to work with changes to GridField.js

## 1.3.4

* Fix delivery name variable in email template

## 1.3.5

* Add delivery info to invoice template

## 1.3.6

* Improve export fields (inline with catalogue admin)

## 1.3.7

* Ensure LineItems get a stock item from the current subsite (if subsites is installed)

## 1.3.8

* Added customisations to Invoice template

## 1.3.9

* Only get order notifications from currently active SiteConfig (so notifications don't leek from other subsites)

## 1.3.10

* Allow adding a LineItemFactory (with assotiated LineItem) directly to an OrderFactory

## 1.3.11

* Update LineItem extension hook
* Simplify stock alert messages

## 1.3.12

* added fix to LineItemFactory to cast null boolean values to false

## 1.3.13

* Add missing output to `OrderFactory::setCustomer()`

## 1.3.14

* Fix logo image in PDF
* Improve default PDF styling a little

## 1.3.15

* Make Estimate/Invoice PDf CSS public dir aware

## 1.3.16

* Additional invoice PDF CSS tweaks

## 1.3.17

* Only lock line items via the factory, if explicitly asked to

## 1.3.18

* Add ability to find best tax rate for a line item
* Add more unit tests

## 1.3.19

* Fix error when checking out using a fixed tax rate on a product

## 1.3.20

* Allow line items with a base price of zero to be added

## 1.3.21

* Add customer info and discounts to email templates

## 1.3.22

* Ensure that when an estimate is converted to an invoice, it is actually an estimate
* Forcefully regenerate invoice ref and prefix on conversion (rather than relying on onBeforeWrite)

## 1.4.0

* Improve Estimate/Invoice Add line item to be independent of GridFieldRelationAdd
* Switch to new notifications system for order notifications

## 1.4.1

* Re-add status and reference filter fields to admin

## 1.4.2

* Ensure status can be left blank when filtering

## 1.4.3

* Fix error manually adding line items to an estimate/invoice

## 1.4.4

* Ensure that when an estimate is converted to an invoice, it is actually an estimate
* Forcefully regenerate invoice ref and prefix on conversion (rather than relying on onBeforeWrite)

## 1.4.5

* Add search context from 1.5 branch

## 1.4.6

* Add UUID's to orders
* Switch to using UUID and Key for frontend display
* Allow manually setting of an order in OrderFactory

## 1.5.0

* Add ability to specify if estimate can have a negative value
* Migrate some logic from Estimates/Invoices to a factory
* Tidy up code folder, PSR4 folder naming
* Add basic versioning to line items as well as Estimates/Invoices
* Set Estimate/Invoice title to use full ref
* Ensure that when an estimate is converted to an invoice, it is actually an estimate
* Forcefully regenerate invoice ref and prefix on conversion (rather than relying on onBeforeWrite)
