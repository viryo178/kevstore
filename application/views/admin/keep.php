<main id="main" class="main keep-bg">

    <div class="pagetitle">
        <h1 class="text-white">Notes</h1>
        <p class="text-secondary">Klik card untuk edit seperti Google Keep</p>
    </div>

    <section class="section">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="text-white mb-0">Semua Catatan</h4>

            <button class="btn btn-glow" onclick="openNewNote()">
                <i class="bi bi-plus"></i> Tambah Catatan
            </button>
        </div>

        <div class="keep-grid">

            <?php foreach ($notes as $n): ?>

                <div class="keep-card"
                    data-id="<?= $n->id ?>"
                    data-title="<?= htmlspecialchars((string)$n->title, ENT_QUOTES) ?>"
                    data-content="<?= htmlspecialchars((string)$n->content, ENT_QUOTES) ?>">

                    <div class="keep-title">
                        <?= htmlspecialchars((string)$n->title) ?>
                    </div>

                    <div class="keep-content">
                        <?= nl2br(htmlspecialchars((string)$n->content)) ?>
                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    </section>
</main>
<div id="keepModal" class="keep-modal">

    <div class="keep-modal-box">

        <input type="hidden" id="note_id">

        <input type="text"
            id="note_title"
            class="keep-title-input"
            placeholder="Judul catatan...">

        <textarea id="note_content"
            class="keep-content-input"
            placeholder="Tulis catatan..."></textarea>

    </div>

</div>

<script>
    function saveNote() {

        let id = document.getElementById("note_id").value;
        let title = document.getElementById("note_title").value;
        let content = document.getElementById("note_content").value;

        fetch("<?= base_url('admin/save_note') ?>", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: new URLSearchParams({
                    id: id,
                    title: title,
                    content: content
                })
            })
            .then(res => res.json())
            .then(res => {

                console.log("SAVE OK", res);

                // update id setelah insert baru
                if (!id) {
                    document.getElementById("note_id").value = res.id;
                }

            })
            .catch(err => {
                console.log("SAVE ERROR", err);
            });
    }
    let typingTimer;

    document.getElementById("note_title").addEventListener("input", autoSave);
    document.getElementById("note_content").addEventListener("input", autoSave);

    function autoSave() {
        clearTimeout(typingTimer);

        typingTimer = setTimeout(() => {
            saveNote();
        }, 600);
    }
</script>