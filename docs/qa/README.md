# Just Bookeit QA Pack

## Files
| File | Use |
|------|-----|
| `docs/qa/test-cases.csv` | Open in Excel/Google Sheets — run tests, fill Actual Result + Pass/Fail |
| `docs/qa/bug-sheet.csv` | Log / track bugs (validation, functional, responsive, security) |
| Cursor canvas `just-bookeit-qa-test-suite.canvas.tsx` | Interactive filter view in Cursor |

## How to test
1. Open `test-cases.csv` in Excel.
2. Filter Priority = P0 first.
3. For each row: run **Test Steps**, write **Actual Result**, set **Pass/Fail** = Pass or Fail.
4. On Fail: copy a new row into `bug-sheet.csv` (or mark existing BUG-* Status).
5. Responsive: use Chrome DevTools device toolbar — widths 320, 375, 390, 414, 768, 1024, 1280.

## Breakpoints
- Mobile: 375 / 390
- Tablet: 768
- Desktop: 1280+

## Critical bugs to verify first
- BUG-V03 Refund unbound amount
- BUG-V04 OTP in API response
- BUG-B01 Cancel accepted item desync
- BUG-R06 Admin mobile sidebar
- BUG-SEC01 Sub-admin city scope
