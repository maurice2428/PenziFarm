# Kenya Compliance Notes

The package is designed to support Kenyan payroll and expense accounting, but
legal compliance depends on the entity, transaction, supplier, registration
status, and effective date.

## Payroll

- PAYE uses date-effective monthly bands and personal relief.
- PAYE, SHIF, and normal monthly payroll deadlines are tracked.
- NSSF rates are stored by effective date so later phases can be added without
  changing historical payrolls.
- AHL includes both employee and employer contributions.
- Working-day calculations exclude weekends but do not automatically exclude
  gazetted public holidays.

## Operating expenses

- VAT is posted to Input VAT only when the user confirms it is claimable.
- Non-claimable VAT becomes part of the expense cost.
- eTIMS invoice/control references and supplier KRA PINs are stored.
- WHT rate defaults change based on the selected category and supplier
  residency, but the user must confirm the actual legal classification.
- Expense evidence can be attached and retained with the accounting source.

## Audit controls

- Posted records are reversed rather than deleted.
- Reversal journals preserve the original financial trail.
- Source posting keys prevent duplicate journals.
- Draft records may be safely deleted before posting.
- Payment references and timestamps are retained.
