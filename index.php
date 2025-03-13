<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>jocarsa | darkolivegreen</title>
  <link rel="stylesheet" href="css/styles.css">
</head>
<body>
  <div class="container">
    <!-- Sidebar: Logo and navigation (Column 1) -->
    <div class="sidebar">
      <div class="logo">
        <img src="darkolivegreen.png" alt="Corporate Logo">
      </div>

      <!-- ADDED USAGE BAR PLACEHOLDER HERE -->
      <div id="usage-bar" style="margin-bottom: 20px;">
        <!-- Filled dynamically by script.js (loadFolders()) -->
      </div>
      <!-- END USAGE BAR PLACEHOLDER -->

      <nav class="tools">
        <ul>
          <!-- New "Enviar y recibir" button triggers sync -->
          <li><button id="sync-button">Enviar y recibir</button></li>
          <li><button data-view="compose">Compose</button></li>
          <li><button data-view="mail">Mail</button></li>
          <li><button data-view="contacts">Contacts</button></li>
          <li><button data-view="calendar">Calendar</button></li>
          <li><button data-view="settings">Settings</button></li>
          <li><a href="logout.php">Logout</a></li>
        </ul>
      </nav>
    </div>
    
    <!-- Main content area (Columns 2-4) -->
    <div class="main-content">
      <!-- Mail View -->
      <section id="mail-view" class="view">
        <div class="mail-container">
          <div class="email-trays">
            <ul id="folder-list">
              <!-- Folders loaded dynamically -->
            </ul>
          </div>
          <div class="email-list-container">
            <!-- Search container on top of the email list -->
            <div class="search-container">
              <input type="text" id="search-input" placeholder="Search...">
            </div>
            <ul id="email-list">
              <!-- Emails loaded dynamically -->
            </ul>
          </div>
          <div class="email-content" id="email-content">
            <div class="placeholder">
              <p>Select an email to view its content</p>
            </div>
          </div>
        </div>
      </section>
      
      <!-- Compose View -->
      <section id="compose-view" class="view" style="display:none;">
        <div class="compose-container">
          <h2>Compose Email</h2>
          <form id="compose-form">
            <label for="to">To:</label>
            <input type="email" id="to" name="to" required>
            <label for="subject">Subject:</label>
            <input type="text" id="subject" name="subject" required>
            <label for="message">Message:</label>
            <textarea id="message" name="message" rows="10" required></textarea>
            <button type="submit">Send</button>
          </form>
        </div>
      </section>
      
      <!-- Contacts View (with CRUD) -->
      <section id="contacts-view" class="view" style="display:none;">
        <div class="contacts-container">
          <h2>Contacts</h2>
          <div id="contacts-list-container">
            <!-- Contacts list (rendered as a table) -->
          </div>
          <h3>Add / Edit Contact</h3>
          <form id="contact-form">
            <input type="hidden" id="contact-id" name="id" value="">
            <label for="contact-name">Name:</label>
            <input type="text" id="contact-name" name="name" required>
            <label for="contact-email">Email:</label>
            <input type="email" id="contact-email" name="email" required>
            <button type="submit">Save Contact</button>
            <button type="button" id="cancel-edit" style="display:none;">Cancel</button>
          </form>
        </div>
      </section>
      
      <!-- Calendar View -->
      <section id="calendar-view" class="view" style="display:none;">
        <div class="calendar-container">
          <h2>Calendar</h2>
          <ul id="calendar-list">
            <!-- Calendar events loaded dynamically -->
          </ul>
        </div>
      </section>
      
      <!-- Settings View -->
      <section id="settings-view" class="view" style="display:none;">
        <div class="settings-container">
          <h2>Settings</h2>
          <form id="settings-form">
            <label for="signature">Email Signature:</label>
            <textarea id="signature" name="signature" rows="4"></textarea>
            <label for="theme">Theme:</label>
            <select id="theme" name="theme">
              <option value="light">Light</option>
              <option value="dark">Dark</option>
            </select>
            <button type="submit">Save Settings</button>
          </form>
        </div>
      </section>
      
    </div>
  </div>
  
  <script src="js/script.js"></script>
  <link rel="stylesheet" href="https://jocarsa.github.io/jocarsa-snow/jocarsa%20%7C%20snow.css">
  <script src="https://jocarsa.github.io/jocarsa-snow/jocarsa%20%7C%20snow.js"></script>
</body>
</html>

