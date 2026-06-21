<main id="main" class="main">

  <div class="pagetitle">
    <h1>Chat</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= base_url('admin') ?>">Home</a></li>
        <li class="breadcrumb-item active">Chat</li>
      </ol>
    </nav>
  </div><!-- End Page Title -->

  <section class="section">
    <div class="row">
      <div class="col-lg-8">
        <div class="card" style="border:1px solid rgba(255,255,255,0.08); background: rgba(15,23,42,0.6);">
          <div class="card-body">
            <h5 class="card-title" style="color:#60a5fa;">Ruang Chat</h5>

            <div id="chatBox" style="height:400px; overflow-y:auto; background: rgba(2,6,23,0.4); padding:12px; border-radius:8px; margin-bottom:12px;"></div>

            <form id="chatForm">
              <div class="input-group">
                <input type="text" id="chatMessage" class="form-control" placeholder="Tulis pesan...">
                <button class="btn btn-primary" type="submit" style="background:#3b82f6;">Kirim</button>
              </div>
            </form>

          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card" style="border:1px solid rgba(255,255,255,0.08); background: rgba(15,23,42,0.6);">
          <div class="card-body">
            <h5 class="card-title" style="color:#60a5fa;">Pesan Terbaru</h5>
            <p style="color:#94a3b8;">Chat menggunakan database `chat_messages`. Pesan akan disimpan di server.</p>
          </div>
        </div>
      </div>

    </div>
  </section>

</main>

<script>
  const chatBox = document.getElementById('chatBox');
  const chatForm = document.getElementById('chatForm');
  const chatMessage = document.getElementById('chatMessage');

  function renderMessages(messages) {
    chatBox.innerHTML = '';
    messages.forEach(m => {
      const el = document.createElement('div');
      el.style.marginBottom = '8px';
      el.innerHTML = `<strong style="color:#60a5fa;">${m.sender}</strong>: <span style="color:#e2e8f0;">${m.message}</span> <div style="color:#94a3b8; font-size:11px;">${new Date(m.created_at).toLocaleString()}</div>`;
      chatBox.appendChild(el);
    });
    chatBox.scrollTop = chatBox.scrollHeight;
  }

  async function loadMessages() {
    try {
      const res = await fetch('<?= base_url("admin/get_messages") ?>');
      const data = await res.json();
      if (data.status === 'success') {
        renderMessages(data.data);
      }
    } catch (e) {
      console.error(e);
    }
  }

  chatForm.addEventListener('submit', async function(e) {
    e.preventDefault();
    const msg = chatMessage.value.trim();
    if (!msg) return;
    try {
      const fd = new FormData();
      fd.append('message', msg);
      // attach CSRF token if available
      const csrfNameMeta = document.querySelector('meta[name="csrf-name"]');
      const csrfHashMeta = document.querySelector('meta[name="csrf-hash"]');
      if (csrfNameMeta && csrfHashMeta) fd.append(csrfNameMeta.content, csrfHashMeta.content);
      const res = await fetch('<?= base_url("admin/send_message") ?>', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.status === 'success') {
        chatMessage.value = '';
        loadMessages();
      } else {
        showToast('Gagal mengirim pesan', 'danger');
      }
    } catch (e) {
      console.error(e);
    }
  });

  // Poll every 3s
  loadMessages();
  setInterval(loadMessages, 3000);
</script>
