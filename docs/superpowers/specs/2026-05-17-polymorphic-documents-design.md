# Polymorphic Documents Design

**Date:** 2026-05-17

## Overview

A top-level `documents` table and `Document` model that can attach to any model in the system. Each document record represents one file (stored via Spatie Media Library) plus structured metadata about that document.

## Database Schema

Table: `documents`

| Column | Type | Nullable | Notes |
|---|---|---|---|
| `id` | bigint | no | PK |
| `documentable_type` | string | no | Polymorphic type |
| `documentable_id` | bigint | no | Polymorphic ID |
| `type` | string | no | `DocumentType` enum value |
| `name` | string | no | Display name for the document |
| `document_date` | date | yes | The date on the document itself (e.g. lab collection date, form signature date) — distinct from `created_at` |
| `notes` | text | yes | Free-form notes |
| `created_at` | timestamp | yes | |
| `updated_at` | timestamp | yes | |
| `deleted_at` | timestamp | yes | Soft deletes |

Index: `documentable_type` + `documentable_id` (standard Laravel `morphs()`)

## Document Model

`App\Models\Document`

- Implements Spatie `HasMedia` with a single `file` media collection (enforced as single-file)
- Uses `InteractsWithMedia` trait
- Uses `SoftDeletes` trait
- Uses `LogsActivity` trait (consistent with Patient, Contact, etc.)
- `MorphTo` relation: `documentable()`
- Casts: `type` → `DocumentType`, `document_date` → `date`
- Fillable: `type`, `name`, `document_date`, `notes`
- Activity log tracks: `type`, `name`, `document_date`, `notes` (dirty only)

## DocumentType Enum

`App\Enums\DocumentType`

Values (TitleCase keys, string-backed):
- `LabResult`
- `Insurance`
- `Referral`
- `Consent`
- `Prescription`
- `Identification`
- `Certification`
- `Note`
- `Other`

## Relationships Added to Existing Models

Each of the following models gets a `documents(): MorphMany` relation pointing to `Document`:

- `Patient`
- `Appointment`
- `User`
- `Contact`

This pattern matches existing polymorphic relations in the app (e.g. `contacts()`, `discussions()`).

## DocumentFactory

A `DocumentFactory` is created for use in tests. It generates realistic fake data for all fillable fields and sets a random `DocumentType`. It does not automatically attach a media file (media is handled separately in tests that need it).

## Architecture Notes

- The `Document` model is the `HasMedia` owner — files are stored in Spatie's `media` table with `model_type = App\Models\Document`. This means the `documentable` (e.g. Patient) does not need to implement `HasMedia` solely for documents — it uses `Document` as an intermediary.
- `Patient` already implements `HasMedia` for avatars; adding documents via the `Document` model keeps avatar and document media collections cleanly separated.
- Each `Document` has exactly one file. The media collection is registered as `singleFile()`.

## Testing

- Feature test covering: creating a document (with file) attached to each documentable model type, soft delete, and type casting.
- Test that `documents()` MorphMany returns the correct records when multiple documentable types exist in the database (isolation).
