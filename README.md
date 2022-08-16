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
* The code generates individual frames of video
* The video is composited from 5 layers of generated images:
  * base map (highlighting countries Kiva is active in)
  * lenders (showing currently-active lenders)
  * borrowers (showing currently-active borrowers)
  * loans (showing currently-active loans, in their path from lender to borrower and back)
  * statistics (showing current number of lenders, borrowers, and amount loaned)
* Data for loan activity was extracted from the main Kiva database and transformed into a time-series format 
* The code allows for generation of frames containing:
  * All loan activity
  * Loan activity for a specific sector (Agriculture, Education, Post-conflict regions, etc.)
* After generation, frames are combined into video (.mp4) files by using ffmpeg, and then manually edited together
  
