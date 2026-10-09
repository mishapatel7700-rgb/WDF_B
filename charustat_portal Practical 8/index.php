<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CHARUSAT Student Portal - Events</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <button id="themeBtn" class="theme-btn" type="button">🌙 Dark Mode</button>

  <header class="site-header">
    <h1>CHARUSAT Student Portal</h1>
    <p>Everything you need for your academic journey in one place.</p>
    <hr>
    <nav class="main-nav">
      <a href="index.php">Dashboard</a><span>|</span>
      <a href="#about">About</a><span>|</span>
      <a href="#events">Events</a>
    </nav>
  </header>

  <main id="events">
    <h2 class="section-title">CHARUSAT CAMPUS EVENTS</h2>

    <section class="controls" aria-label="Search and filter events">
      <input id="searchInput" type="search" placeholder="Search events..." aria-label="Search events">
      <select id="categoryFilter" aria-label="Filter by category">
        <option value="all">All Categories</option>
      </select>
      <select id="sortSelect" aria-label="Sort events">
        <option value="date-asc">Date: Oldest First</option>
        <option value="date-desc">Date: Newest First</option>
        <option value="title-asc">Title: A-Z</option>
        <option value="title-desc">Title: Z-A</option>
      </select>
    </section>

    <div class="result-info"><p id="resultCount">Loading events...</p></div>
    <section id="eventsContainer" class="events-container" aria-live="polite"></section>

    <div class="pagination">
      <button id="prevBtn" type="button">Previous</button>
      <strong id="pageInfo">Page 1 of 1</strong>
      <button id="nextBtn" type="button">Next</button>
    </div>
  </main>

  <footer id="about"><a href="#events">HOME</a><p>CHARUSAT Student Portal</p></footer>

  <!-- The registration form is hidden until a Register Now button is clicked. -->
  <div id="registrationModal" class="modal" aria-hidden="true">
    <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
      <button id="closeModal" class="close-modal" type="button" aria-label="Close registration form">&times;</button>
      <h2 id="modalTitle">Event Registration</h2>
      <p id="selectedEvent" class="selected-event"></p>
      <form id="registrationForm">
        <input type="hidden" id="eventId" name="event_id">
        <label for="name">Name</label>
        <input id="name" name="name" type="text" minlength="2" maxlength="100" required>
        <label for="email">Email</label>
        <input id="email" name="email" type="email" maxlength="100" required>
        <label for="phone">Phone</label>
        <input id="phone" name="phone" type="tel" inputmode="numeric" pattern="[0-9]{10}" maxlength="10" placeholder="10 digit phone number" required>
        <button class="primary-btn submit-btn" type="submit">Submit Registration</button>
      </form>
      <p id="registrationMessage" role="status"></p>
    </div>
  </div>

  <script src="script.js"></script>
</body>
</html>
