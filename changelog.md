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

* Fix error with trimming estimate/invoice numbers.

## 1.2.0

* Split estimate/invoice numbers to use seperate reference and prefix.
* Add migration tool to estimates/invoices to new ref/prefix config.

## 1.2.1

* Replace references to Number with FullRef
* Remove legact reports (replaced by reports module)