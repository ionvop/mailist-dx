# mailist DX

We're working on a chart sharing platform and collaborative tool for the rhythm game maimai with tools such as MajdataEdit for the chart editor and AstroDX for simulating the game on mobile.

## Layouts

Each navigation tabs will have an icon associated with it.

Please use `mallardduck/blade-lucide-icons` for the icons.

Please refer to `old/astrodx/` for the design, theme and colors, but no need to match its layout.

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

Please refer to `old/saucepls/` for reference on how this will be implemented.

### Upload chart

This is where the user uploads their chart:

- Upload `.zip` or `.adx` (still a zip file)
    - Find `maidata.txt` and use that as the working directory for the following files
    - Find `image/*` with filename `bg` (e.g. `bg.jpg`) within the directory and set that as the chart BG (chart jacket/song cover/track art)
    - Find `audio/*` with filename `track` (e.g. `track.mp3`) and set that as the chart track
    - Find `video/*` with filename `pv` (e.g. `pv.mp4`) and set that as the chart PV
    - Parse the `maidata.txt` to retrieve chart details such as its title, artist, note designer, etc.
- Individual components such as the chart BG, track, and PV can still be manually set or updated.
- Markdown-supported description for the chart.
- Checkbox on whether to set this chart as private or not.

Please refer to `old/astrodx-web/` for reference on how this will be implemented.

### Chart details

This is the page that opens when checking on a chart. It will have the following navigation tabs:

- Details
    - Two columns
    - Left column: Difficulty listings. Clicking on a difficulty will redirect the user to the difficulty page
    - Right column: Chart details such as the chart BG, title, artist, etc. And a preview of the track
- Comments
- Settings
    - Only accessible to owner
        - Add/remove edit access to other users
        - Set chart status (WIP/For Review/Completed/Approved)
        - Private/unprivate
        - Delete
    - Only accessible to staff
        - Approve
        - Delete

### Chart difficulty page

The is the page that opens when checking on a chart difficulty. It will have the following navigation tabs:

- Code
    - Two columns
    - Left column: Textarea containing the simai code that makes up the chart, edits can only be saved by those with edit access.
    - Right column: Chart preview
- Improvements
    - Other users can select line(s) from the simai code for context and suggests improvements (e.g. ln 45-47 The note placements here doen't match with the song. I suggest `1,2,3,,8,7,6,`)
    - The editors can either reject the suggestion with a reason or mark the suggestion as resolved by selecting the lines that were changed in the new version (there will be a diff visualizer for the suggester's selected lines and the editor's selected lines) and a remark
- Comments
- History
    - Everytime a change is saved, the system saves a snapshot of the change.
    - There will be a diff visualizer for the snapshot and the snapshot before it.
    - Each snapshot will have a "Restore to this state" button which will revert the code back into this snapshot (this will be treated as an edit instead of removing history)

*WIP*