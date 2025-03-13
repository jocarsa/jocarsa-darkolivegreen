document.addEventListener('DOMContentLoaded', function() {
  // Exclude sync button from view switching
  const navButtons = document.querySelectorAll('.tools button:not(#sync-button)');
  const views = document.querySelectorAll('.view');

  navButtons.forEach(btn => {
    btn.addEventListener('click', function() {
      // Hide all views and remove active class
      views.forEach(view => view.style.display = 'none');
      navButtons.forEach(b => b.classList.remove('active'));
      this.classList.add('active');

      const viewName = this.getAttribute('data-view');
      const viewElement = document.getElementById(viewName + '-view');
      if (viewElement) {
        viewElement.style.display = 'block';
      }

      // When Mail view is active, load folders and emails from the database
      if (viewName === 'mail') {
        loadFolders();
      } else if (viewName === 'contacts') {
        loadContacts();
      } else if (viewName === 'calendar') {
        loadCalendar();
      } else if (viewName === 'settings') {
        loadSettings();
      }
    });
  });

  // Set initial view to Mail
  document.querySelector('button[data-view="mail"]').click();

  // Toast helper functions for status messages
  function showToast(message) {
    let toast = document.getElementById('toast');
    if (!toast) {
      toast = document.createElement('div');
      toast.id = 'toast';
      toast.style.position = 'fixed';
      toast.style.bottom = '20px';
      toast.style.right = '20px';
      toast.style.background = 'rgba(0, 0, 0, 0.7)';
      toast.style.color = '#fff';
      toast.style.padding = '10px 20px';
      toast.style.borderRadius = '5px';
      toast.style.fontSize = '14px';
      toast.style.opacity = '0';
      toast.style.transition = 'opacity 0.3s ease';
      document.body.appendChild(toast);
    }
    toast.textContent = message;
    toast.style.opacity = '1';
    return toast;
  }

  function hideToast(toast) {
    toast.style.opacity = '0';
    setTimeout(() => {
      if (toast && toast.parentNode) {
        toast.parentNode.removeChild(toast);
      }
    }, 500);
  }

  // Array to store emails for search filtering
  let allEmails = [];

  // Render emails into the list
  function renderEmails(emails) {
    const emailList = document.getElementById('email-list');
    emailList.innerHTML = '';
    if (emails && emails.length > 0) {
      emails.forEach(email => {
        const li = document.createElement('li');
        li.classList.add('email-item');
        li.setAttribute('data-id', email.id);
        li.innerHTML = `
          <div class="email-sender">${email.sender}</div>
          <div class="email-subject">${email.subject}</div>
          <div class="email-date">${email.date}</div>
        `;
        li.addEventListener('click', function() {
          document.querySelectorAll('.email-item').forEach(item => item.classList.remove('active'));
          li.classList.add('active');
          showEmailContent(email);
        });
        emailList.appendChild(li);
      });
    } else {
      emailList.innerHTML = '<li>No emails found in this folder.</li>';
      document.getElementById('email-content').innerHTML = '<div class="placeholder"><p>No email selected</p></div>';
    }
  }

  // Display full email content
  function showEmailContent(email) {
    const emailContent = document.getElementById('email-content');
    emailContent.innerHTML = `
      <div class="email-header">
        <h2>${email.subject}</h2>
        <div class="email-meta">
          <span>From: ${email.sender}</span>
          <span>Date: ${email.date}</span>
        </div>
      </div>
      <div class="email-body">
        ${email.body}
      </div>
    `;
  }

  // Load emails from the local database (sync=0)
  function loadEmails(folder) {
    fetch('backend/backend.php?type=emails&folder=' + encodeURIComponent(folder) + '&sync=0')
      .then(response => response.json())
      .then(data => {
        allEmails = data || [];
        renderEmails(allEmails);
      })
      .catch(error => console.error('Error loading local emails:', error));
  }

  // Synchronize emails by connecting to the mail server (sync=1)
  function syncEmails() {
    // Determine the active folder; default to INBOX if none is active
    const activeFolderElement = document.querySelector('#folder-list li.active');
    const folder = activeFolderElement ? activeFolderElement.getAttribute('data-folder') : 'INBOX';

    const toast = showToast('Sincronizando emails...');
    fetch('backend/backend.php?type=emails&folder=' + encodeURIComponent(folder) + '&sync=1')
      .then(response => response.json())
      .then(updatedData => {
        // After syncing, reload emails from the database
        loadEmails(folder);
      })
      .catch(error => console.error('Error synchronizing emails:', error))
      .finally(() => {
        hideToast(toast);
      });
  }

  // Attach sync function to the "Enviar y recibir" button
  const syncButton = document.getElementById('sync-button');
  if (syncButton) {
    syncButton.addEventListener('click', function() {
      syncEmails();
    });
  }

  // ===== MODIFIED loadFolders() to parse {trays, usage} and build usage bar =====
  function loadFolders() {
    fetch('backend/backend.php?type=folders')
      .then(response => response.json())
      .then(data => {
        // data should look like: { trays: [...], usage: {...} }
        const folderList = document.getElementById('folder-list');
        folderList.innerHTML = '';

        const trays = data.trays || [];
        if (trays.length > 0) {
          trays.forEach(folderName => {
            const li = document.createElement('li');
            li.textContent = folderName;
            li.setAttribute('data-folder', folderName);
            li.addEventListener('click', function() {
              document.querySelectorAll('#folder-list li').forEach(el => el.classList.remove('active'));
              li.classList.add('active');
              loadEmails(folderName);
            });
            folderList.appendChild(li);
          });
          // Activate the first folder by default
          folderList.firstChild.classList.add('active');
          loadEmails(trays[0]);
        } else {
          folderList.innerHTML = '<li>No folders found</li>';
        }

        // Now handle mailbox usage data
        const usage = data.usage || {};
        const usageBar = document.getElementById('usage-bar');
        if (usageBar && usage.limitMB > 0) {
          usageBar.innerHTML = ''; // Clear previous content if any

          const percentage = usage.percentage;
          const usedMB = usage.usedMB;
          const limitMB = usage.limitMB;

          // Display text: "X% - Y MB of Z MB used"
          const usageText = document.createElement('div');
          usageText.textContent = `${percentage}% - ${usedMB} MB of ${limitMB} MB used`;

          // Build a simple horizontal bar
          const progressOuter = document.createElement('div');
          progressOuter.style.width = '100%';
          progressOuter.style.background = '#ccc';
          progressOuter.style.borderRadius = '5px';
          progressOuter.style.margin = '5px 0';

          const progressInner = document.createElement('div');
          progressInner.style.width = `${percentage}%`;
          progressInner.style.height = '10px';
          progressInner.style.background = '#4caf50';
          progressInner.style.borderRadius = '5px';

          progressOuter.appendChild(progressInner);

          usageBar.appendChild(usageText);
          usageBar.appendChild(progressOuter);
        }
      })
      .catch(error => console.error('Error fetching folders:', error));
  }
  // ===== END MODIFIED loadFolders() =====

  // Implement client-side email search
  const searchInput = document.getElementById('search-input');
  if (searchInput) {
    searchInput.addEventListener('input', function() {
      const query = this.value.toLowerCase();
      const filtered = allEmails.filter(email => {
        const text = (email.sender + ' ' + email.subject + ' ' + email.date).toLowerCase();
        return text.includes(query);
      });
      renderEmails(filtered);
    });
  }

  // Compose Email
  const composeForm = document.getElementById('compose-form');
  if (composeForm) {
    composeForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const formData = new FormData(composeForm);
      fetch('send_email.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert(data.success);
        } else {
          alert(data.error);
        }
        composeForm.reset();
      })
      .catch(error => {
        console.error('Error sending email:', error);
        alert('Error sending email.');
      });
    });
  }

  // Contacts CRUD
  function loadContacts() {
    fetch('backend/backend.php?type=contacts')
      .then(response => response.json())
      .then(data => {
        renderContacts(data);
      })
      .catch(error => console.error('Error loading contacts:', error));
  }

  function renderContacts(contacts) {
    const container = document.getElementById('contacts-list-container');
    container.innerHTML = '';
    if (contacts && contacts.length > 0) {
      const table = document.createElement('table');
      table.innerHTML = `<tr><th>Name</th><th>Email</th><th>Actions</th></tr>`;
      contacts.forEach(contact => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${contact.name}</td>
          <td>${contact.email}</td>
          <td>
            <button class="edit-contact" data-id="${contact.id}" data-name="${contact.name}" data-email="${contact.email}">Edit</button>
            <button class="delete-contact" data-id="${contact.id}">Delete</button>
          </td>
        `;
        table.appendChild(tr);
      });
      container.appendChild(table);
    } else {
      container.innerHTML = '<p>No contacts found.</p>';
    }
  }

  const contactForm = document.getElementById('contact-form');
  const cancelEditBtn = document.getElementById('cancel-edit');
  if (contactForm) {
    contactForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const id = document.getElementById('contact-id').value;
      const name = document.getElementById('contact-name').value;
      const email = document.getElementById('contact-email').value;
      let op = id ? 'update' : 'add';
      const formData = new FormData();
      if (id) formData.append('id', id);
      formData.append('name', name);
      formData.append('email', email);

      fetch('backend/backend.php?type=contacts&op=' + op, {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          loadContacts();
          contactForm.reset();
          cancelEditBtn.style.display = 'none';
        } else {
          alert(data.error);
        }
      })
      .catch(error => {
        console.error('Error processing contact:', error);
        alert('Error processing contact.');
      });
    });
  }

  const contactsContainer = document.getElementById('contacts-list-container');
  if (contactsContainer) {
    contactsContainer.addEventListener('click', function(e) {
      if (e.target.classList.contains('edit-contact')) {
        const id = e.target.getAttribute('data-id');
        const name = e.target.getAttribute('data-name');
        const email = e.target.getAttribute('data-email');
        document.getElementById('contact-id').value = id;
        document.getElementById('contact-name').value = name;
        document.getElementById('contact-email').value = email;
        cancelEditBtn.style.display = 'inline';
      } else if (e.target.classList.contains('delete-contact')) {
        if (confirm('Delete this contact?')) {
          const id = e.target.getAttribute('data-id');
          const formData = new FormData();
          formData.append('id', id);
          fetch('backend/backend.php?type=contacts&op=delete', {
            method: 'POST',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              loadContacts();
            } else {
              alert(data.error);
            }
          })
          .catch(error => {
            console.error('Error deleting contact:', error);
            alert('Error deleting contact.');
          });
        }
      }
    });
  }

  if (cancelEditBtn) {
    cancelEditBtn.addEventListener('click', function() {
      contactForm.reset();
      cancelEditBtn.style.display = 'none';
    });
  }

  // Calendar
  function loadCalendar() {
    fetch('backend/backend.php?type=calendar')
      .then(response => response.json())
      .then(data => {
        const calendarList = document.getElementById('calendar-list');
        calendarList.innerHTML = '';
        if (data && data.length > 0) {
          data.forEach(event => {
            const li = document.createElement('li');
            li.innerHTML = `<strong>${event.title}</strong> - ${event.date} ${event.time}`;
            calendarList.appendChild(li);
          });
        } else {
          calendarList.innerHTML = '<li>No calendar events found.</li>';
        }
      })
      .catch(error => console.error('Error fetching calendar events:', error));
  }

  // Settings
  function loadSettings() {
    fetch('backend/backend.php?type=settings')
      .then(response => response.json())
      .then(data => {
        if (data) {
          document.getElementById('signature').value = data.signature || '';
          document.getElementById('theme').value = data.theme || 'light';
        }
      })
      .catch(error => console.error('Error fetching settings:', error));
  }

  const settingsForm = document.getElementById('settings-form');
  if (settingsForm) {
    settingsForm.addEventListener('submit', function(e) {
      e.preventDefault();
      alert('Settings saved!');
    });
  }
});

