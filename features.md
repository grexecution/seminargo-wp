# Hotel Sync Features

This document outlines the key features and functionalities of the Seminargo Hotel Importer.

## Core Features

- **GraphQL Integration**: Connects to the external Seminargo GraphQL API (`dev.seminargo.eu`) to fetch hotel data.
- **Batched Importing**:
  - **Manual Import**: Fetches hotels in safe batches (size: 20) to prevent server timeouts and memory exhaustion.
  - **Auto-Import (Cron)**: Runs periodically via WP-Cron, processing small batches (20 hotels) at a time to ensure continuous synchronization without impacting site performance.
- **Mark & Sweep Synchronization**:
  - Tracks when each hotel was last synced (`_last_synced_at`).
  - Automatically identifies and drafts hotels that are no longer present in the API feed at the end of a sync cycle.
- **Robust Image Handling**:
  - Downloads hotel images from external URLs.
  - Dedupes images to prevent cluttering the media library.
  - Bypasses strict WordPress MIME type checks to ensure valid images from the API are accepted (e.g., if extension/mime mismatch).
  - Automatically sets the first image as the Featured Image.
- **Data Mapping**:
  - Maps API fields to WordPress post title, content, and custom meta fields.
  - Handles complex data structures like Meeting Rooms, Amenities, and Cancellation Rules (stored as JSON or individual meta).
  - Calculates total capacity and room counts based on meeting room data if not provided directly.

## Admin Features

- **Live Progress Monitoring**: Real-time log display in the WP Admin dashboard showing import status, errors, and success messages.
- **Manual Controls**:
  - "Fetch Now" button to trigger a manual sync immediately.
  - "Reset" button to clear sync progress and logs.
  - "Delete All Hotels" utility for development/cleanup.
- **Quick Edit Support**:
  - "Featured on Landing Page" checkbox available in the Quick Edit view for hotels.
- **Custom Columns**:
  - Admin list view shows key stats: Image, Location, Rating, Rooms, Capacity, and "Featured" status.

## Technical Details

- **Post Type**: `hotel`
- **Taxonomies**: None (currently uses meta fields for categorization).
- **Meta Fields**:
  - `hotel_id` (Unique API ID)
  - `_last_synced_at` (Timestamp of last sync)
  - `business_city`, `business_country`, `stars`, `rating`, etc.
  - `medias_json`, `meeting_rooms`, `cancellation_rules` (JSON blobs for complex data).

## Future Improvements

- **Taxonomy Integration**: Convert amenities or locations to true WordPress taxonomies for better filtering.
- **Frontend Search**: Advanced search based on the synced meta data.
