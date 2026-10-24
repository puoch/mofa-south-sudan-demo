# 0005. Editorial workflow with separate review, approval and publication

- Status: Accepted (configurable)
- Date: 2026-10-24

## Decision
One core Content Moderation workflow applies to every content type and to
image and document media:

Draft → In review → Approved for publication → Published → Archived

| Transition | From | To | Role |
| --- | --- | --- | --- |
| Save draft | Draft, Published | Draft | Author, reviewer, publisher |
| Submit for review | Draft | In review | Author |
| Return to draft | In review, Approved | Draft | Reviewer, publisher |
| Approve | In review | Approved | Reviewer |
| Publish | Approved | Published | Publisher |
| Archive | Published | Archived | Publisher |
| Restore to draft | Archived | Draft | Publisher |

There is no path from Draft or In review straight to Published, and an
archived item must be reviewed again before it returns.

## Joint approval
The Ministry's joint approval arrangement is not yet defined. Additional
review states (for example Legal review) can be added by an administrator in
the workflow settings without code. Multi-signature approval would need a
contributed or custom module and is deferred until the arrangement is known.
