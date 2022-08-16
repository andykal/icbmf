# ICBMF
## Intercontinental Ballistic Microfinance
(note: this code is for illustrative purposes only and does not contain all components needed to run)

### Visualizing 10 years of Kiva microloan activity
This code was used to generate the visualizations in the following videos:

https://vimeo.com/28413747?embedded=true&source=vimeo_logo&owner=5173862

https://www.kiva.org/blog/be-the-spark-that-changes-the-world

### Project goal 
* create a visual timelapse of Kiva loan activity between individual lenders and borrowers
  * an individual loan travels from the lender to the borrower and then back to the lender
  * the amount of time required for the loan's round trip is the loan term

### Implementation details
* Implemented using PHP, SQLite, FFMPEG, and iMovie
* The code generates individual frames of video, in the style of stop-motion animation
  * These frames are then stitched together at 24 fps to generate video
* The video is composited from 5 layers of generated images:
  * base map (highlighting countries Kiva is active in) [generate_country_background.php]
  * lenders (showing currently-active lenders) [ChocoRenderer.php]
  * borrowers (showing currently-active borrowers) [ChocoRenderer.php]
  * loans (showing currently-active loans, in their path from lender to borrower and back) [ChocoRenderer.php]
  * statistics (showing current number of lenders, borrowers, and amount loaned) [running_totals.php]
* Data for loan activity was extracted from the main Kiva database and transformed into a time-series format [generate_data.php]
* The code allows for generation of frames containing:
  * All loan activity
  * Loan activity for a specific sector (Agriculture, Education, Post-conflict regions, etc.)
* After generation, frames are combined into video (.mp4) files by using ffmpeg, and then manually edited together
* The code was designed to run as multiple instances in an AWS render farm, to generate multiple video sequences in parallel
  
