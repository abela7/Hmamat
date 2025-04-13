# User Identification System for HMAMAT

This document explains the user identification system implemented in the HMAMAT application to prevent user conflicts and ensure each user maintains their own identity across sessions.

## Overview

Since the application uses baptismal names for user identification (which could potentially conflict), we've implemented a multi-layered identification system that helps distinguish users even when they share the same baptismal name.

## Identification Methods

The system uses several pieces of information to create a unique identity for each user:

1. **Unique ID**: A generated MD5 hash created during registration that combines:
   - IP address
   - User agent (browser/device information)
   - Current timestamp
   - Random number

2. **Persistent Cookie**: A cookie containing the user's unique ID is stored on their device for 30 days to identify returning users.

3. **User Tracking Information**:
   - Email address (optional)
   - IP address
   - User agent
   - Last login timestamp

## How It Works

### During Registration

1. When a user registers, a unique ID is generated based on their IP, user agent, and other factors.
2. Their baptismal name, password, and this unique ID are stored in the database.
3. Additional tracking information is also stored.

### During Login

1. The system first checks for a persistent cookie with a unique ID.
2. If found, it attempts to identify the user by this unique ID.
3. If not found, it tries to identify the user by their IP address and user agent.
4. The baptismal name field is pre-filled if a returning user is detected.
5. Upon successful login, the user's session is created with their unique ID.
6. A persistent cookie is set on their device.

### Across Sessions

Even if a user clears their cookies or logs in from a different browser, there's a good chance the system can identify them based on:
- Their IP address
- User agent information
- Email address (if provided)

## Database Structure

The following database fields have been added to support this system:

### Users Table
- `unique_id`: A unique identifier for each user
- `email`: Optional email address for identification
- `last_ip`: The most recent IP address used
- `user_agent`: Browser and device information
- `last_login`: Timestamp of last login

### User_sessions Table
- `fingerprint`: Additional device identification information

## Privacy Considerations

- IP addresses and user agent information are only used for identification purposes.
- Email addresses are optional and not shared with other users.
- This information is not used for any purpose other than identifying returning users.

## Implementation

To update an existing database to support this system, run the SQL script `db_update.sql` which adds the necessary fields to your database tables. 