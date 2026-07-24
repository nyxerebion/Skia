// notes.js - Simplified with CSRF fix
let noteContent, noteTitle, noteDate, notesList, notesCount, displayID, deleteBtn, editBtn, mode;
let notes = [];
let currentId = null;
let isEdit = false;

function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
}

window.onload = () => {
    noteContent = document.getElementById("noteContent");
    noteTitle = document.getElementById("noteTitle");
    noteDate = document.getElementById("noteDate");
    notesList = document.getElementById("notes-list-content");
    notesCount = document.getElementById("notes-count");
    displayID = document.getElementById("displayID");
    deleteBtn = document.getElementById("deleteNotes");
    editBtn = document.getElementById("editNotes");
    mode = document.getElementById("mode");

    editBtn?.addEventListener("click", toggleEdit);
    deleteBtn?.addEventListener("click", deleteNote);
    document.getElementById("noteForm")?.addEventListener("submit", e => e.preventDefault());

    noteDate.textContent = new Date().toLocaleDateString();
    loadNotes();
};

function loadNotes() {
    fetch("?get_notes")
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                notes = data.notes;
                renderNotes();
            }
        })
        .catch(console.error);
}

function renderNotes() {
    notesList.innerHTML = notes.map(n => `
        <div data-id="${n.id}" onclick="openNote(${n.id})">
            <h3>${escape(n.note_title)}</h3>
            <p>${escape(n.note_content.slice(0, 100))}${n.note_content.length > 100 ? '...' : ''}</p>
        </div>
    `).join("");
    notesCount.textContent = notes.length;
}

function openNote(id) {
    const note = notes.find(n => n.id == id);
    if (!note) return;
    currentId = id;
    displayID.textContent = id;
    noteTitle.value = note.note_title;
    noteContent.value = note.note_content;
    noteDate.textContent = new Date(note.created_at).toLocaleDateString();
    isEdit = false;
    disableInputs(true);
    editBtn.textContent = "EDIT";
    mode.textContent = "(view mode)";
}

function toggleEdit() {
    if (editBtn.textContent === "EDIT") {
        isEdit = true;
        disableInputs(false);
        editBtn.textContent = "SAVE";
        mode.textContent = "(edit mode)";
        noteContent.focus();
    } else {
        saveNote();
    }
}

function disableInputs(disabled) {
    noteContent.disabled = disabled;
    noteTitle.disabled = disabled;
}

function saveNote() {
    const title = noteTitle.value.trim();
    const content = noteContent.value.trim();
    if (!title || !content) {
        alert("Title and content required");
        return;
    }

    const payload = {
        id: currentId || null,
        title,
        content,
        csrf_token: getCsrfToken()
    };

    editBtn.disabled = true;
    editBtn.textContent = "Saving...";

    fetch("", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                if (currentId) {
                    const idx = notes.findIndex(n => n.id == currentId);
                    if (idx > -1) {
                        notes[idx].note_title = title;
                        notes[idx].note_content = content;
                    }
                } else {
                    notes.unshift({ id: data.id, note_title: title, note_content: content, created_at: new Date().toISOString() });
                    currentId = data.id;
                    displayID.textContent = data.id;
                }
                renderNotes();
                disableInputs(true);
                editBtn.textContent = "EDIT";
                isEdit = false;
                mode.textContent = "(view mode)";
                alert("Saved");
            } else {
                throw new Error(data.error || "Save failed");
            }
        })
        .catch(err => alert("Error: " + err.message))
        .finally(() => {
            editBtn.disabled = false;
            editBtn.textContent = "SAVE";
        });
}

function deleteNote() {
    if (!currentId || !confirm("Delete note #" + currentId + "?")) return;

    deleteBtn.disabled = true;
    deleteBtn.textContent = "Deleting...";

    fetch("?delete", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            id: currentId,
            csrf_token: getCsrfToken()
        })
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                notes = notes.filter(n => n.id != currentId);
                renderNotes();
                resetForm();
                alert("Deleted");
            } else {
                throw new Error(data.error || "Delete failed");
            }
        })
        .catch(err => alert("Error: " + err.message))
        .finally(() => {
            deleteBtn.disabled = false;
            deleteBtn.textContent = "Delete";
        });
}

function newNote() {
    if (currentId && (noteTitle.value.trim() || noteContent.value.trim())) {
        if (!confirm("Unsaved changes. Continue?")) return;
    }
    resetForm();
    disableInputs(false);
    editBtn.textContent = "SAVE";
    isEdit = true;
    mode.textContent = "(edit mode)";
    noteTitle.focus();
}

function resetForm() {
    noteTitle.value = "";
    noteContent.value = "";
    currentId = null;
    displayID.textContent = "0";
    noteDate.textContent = new Date().toLocaleDateString();
}

function escape(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
}