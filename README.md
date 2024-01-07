# txtboard 
a fork of 4shout. one php file.  sets up the sqlite3 db for you. works on latest Php version. 
![screenshot](https://github.com/adelia4/txtboard/assets/128197007/fa2e7ccf-6e99-4523-b33f-6e98bb641889)

made by chatgpt and about 30 times of testing and debugging and improving. 
If you want to change anything just feed the code to chatgpt or similar and tell it what to change. 
jan 7 2024 did some serious work with this. Pushed the posting limits. You probably should not go over
100,000 chars but i tested this up to 600,000 chars. Major improvments to reliability. But sqlite3 
is not made to work that hard. I absolutely reccomend you never go over 100,000 chars but its nice to know it 
can be pushed. You set the char limit in the first lines of the file. 10,000 or 20,000 chars is reasonable for \
a board. its way better to set it lower, too. the lower it is, the faster it runs and more reliable it is. 

NOW UPGRADED TO WORK ON PHP8.3 

Simple Posting System
Overview
This PHP script is part of a simple posting system, designed to facilitate user interactions in a forum-like environment. It allows users to create posts and reply to existing ones. The script is developed with PHP and uses SQLite for database management, offering a lightweight yet functional approach to handling user-generated content.

Features
Post Creation and Replies: Users can submit new posts and respond to existing ones. The system supports a nested structure where replies are associated with specific posts.
Character Limit Management: Each post and reply has a maximum character limit, ensuring content remains concise and manageable. This limit is configurable and currently set to 20,000 characters.
Pagination: The main board displays posts with pagination, allowing users to navigate through multiple pages of content seamlessly.
Error Handling and Feedback: The script includes basic error handling, providing feedback to the user in cases such as exceeding the character limit. It also logs the actual length of the post for debugging purposes.
Security Measures: Enhanced security measures are in place, including protection against SQL injection via prepared statements and handling of potential XSS vulnerabilities by sanitizing user input.
CSRF Token Validation: Cross-Site Request Forgery (CSRF) protection is implemented to secure form submissions.
Database Structure
The application uses two main tables in the SQLite database:

posts: Stores the main posts, each with a unique ID, user name, post content, and timestamp.
replies: Stores replies to posts, linking each reply to its parent post through a post ID.
Usage
The script operates in two primary modes:

Main Board Mode: Displays all posts in a paginated format. Users can submit new posts or choose to reply to existing ones.
Reply Mode: When a user selects to reply to a post, the script enters reply mode, displaying the original post and existing replies, along with a form to submit a new reply.
Error Handling and Logs
Error handling is crucial for a smooth user experience. The script handles common errors such as database connection issues and exceeding the post character limit. Additionally, it logs detailed error messages for server-side debugging.

Security Considerations
Security is a top priority in this script. Prepared statements are used to prevent SQL injection, and user inputs are sanitized to mitigate XSS risks. CSRF tokens are employed in forms to enhance security further.

Configuration and Customization
The script allows for easy configuration and customization. Constants such as POSTS_PER_PAGE and MAX_POST_LENGTH can be adjusted to suit different requirements. The styling can be modified via the external CSS file linked in the script.
