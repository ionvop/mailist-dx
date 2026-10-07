# mailist DX

We're working on a chart sharing platform and collaborative tool for the rhythm game maimai with tools such as MajdataEdit for the chart editor and AstroDX for simulating the game on mobile.

## Layouts

### Desktop

The desktop view will have the following layout:

- Left navigation panel
    - Home
    - Search
    - Feed
    - Login (if guest) / Profile (if logged in)
    - List (emphasized)
- Header
    - Notifications
    - Avatar (blank avatar icon if guest)
        - Profile (if logged in)
        - Settings
        - Login button (if guest) / Logout button (if logged in)
- Content

### Mobile

The mobile view will have the following layout:

- Header
    - Notifications
    - Avatar (blank avatar icon if guest)
        - Profile (if logged in)
        - Settings
        - Login button (if guest)
        - Logout button (if logged in)
- Content
- Bottom navigation bar
    - Home
    - Search
    - List (centerpiece)
    - Feed
    - Login (if guest) / Profile (if logged in)

## Pages

### Home

This page will contain the following sections:

- Trending approved charts
- Most recent approved charts
- Trending non-approved charts
- Most recent non-approved charts

### Search

This page will serve as the main exploration area for the platform, with the following filters:

- Filter
    - WIP
    - For Review
    - Completed
    - Approved
- Sort
    - Popularity
    - Trending (sort by how many likes received within a week)
    - Recent

### List

List of charts that the user owns or have edit access. This is where the user uploads their charts.

### Feed

List of all charts that the user has liked and charts from users that the user has followed, sorted by most recent activity.

### Profile

- Avatar
- Following/Follower count
- Markdown-supported Bio
- Charts uploaded
- Charts liked
- Profile comments

### Login

There are only two login methods:

- Get login code by email
- Login with Google

Please refer to `old/login/` for reference on how this will be implemented.

### Upload chart

This is where the user uploads their chart:

- Upload `.zip` or `.adx` (still a zip file)
    - Find `maidata.txt` and use that as the working directory for the following files
    - Find `image/*` with filename `bg` (e.g. `bg.jpg`) within the directory and set that as the chart BG
    - Find `audio/*` with filename `track` (e.g. `track.mp3`) and set that as the chart track
    - Find `video/*` with filename `pv` (e.g. `pv.mp4`) and set that as the chart PV
    - Parse the `maidata.txt` to retrieve chart details such as its title, artist, note designer, etc.
- Individual components such as the chart BG, track, and PV can still be manually set or updated.
- Markdown-supported description for the chart.
- Checkbox on whether to set this chart as private or not.