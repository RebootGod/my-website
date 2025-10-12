# Bulk Operations Guide
**Admin Panel - Movies & Series Management**

---

## 🎯 Overview

Bulk Operations memungkinkan lo untuk melakukan aksi terhadap multiple items sekaligus, menghemat waktu dan effort untuk content management.

---

## 📋 Features

### 1. **Select All / Individual Selection**
- **Checkbox di header**: Select/deselect semua items di current page
- **Checkbox per item**: Select individual movies/series
- **Selection persists**: Tersimpan di localStorage, tetap ada setelah reload page

### 2. **Bulk Action Bar**
Muncul di bottom screen ketika ada items yang dipilih. Menampilkan:
- **Selected count**: Jumlah items yang dipilih
- **Action buttons**: 7 bulk actions available

---

## 🔘 Bulk Actions

### ✅ **Publish** (Green Button)
**Fungsi**: Mengubah status items menjadi "Published"
- Items akan muncul di public website
- Accessible oleh semua users
- Status badge berubah jadi hijau

**Use Case**: 
- Publishing multiple draft movies sekaligus
- Re-publishing archived content

---

### 📄 **Draft** (Orange Button)
**Fungsi**: Mengubah status items menjadi "Draft"
- Items TIDAK muncul di public website
- Hanya visible di admin panel
- Status badge berubah jadi kuning

**Use Case**:
- Temporarily hiding content untuk editing
- Preparing content sebelum publish
- Testing content sebelum go live

---

### 📦 **Archive** (Blue Button)
**Fungsi**: Mengubah status items menjadi "Archived"
- Items TIDAK muncul di public website
- Tetap ada di database tapi hidden
- Status badge berubah jadi abu-abu

**Use Case**:
- Archiving old/outdated content
- Removing dari circulation tanpa delete permanent
- Keeping for historical records

---

### ⭐ **Feature** (Green Star Button)
**Fungsi**: Menandai items sebagai "Featured" (is_featured = true)
- Items muncul di featured sections
- Priority di home page carousel
- Badge "Featured" muncul di item card
- Higher visibility untuk users

**Use Case**:
- Promoting new releases
- Highlighting popular/trending content
- Seasonal promotions (Christmas specials, etc)
- Editor's picks

**Example**: 
- Featured movies muncul di "Featured This Week" section
- Featured series di "Trending Now" carousel
- Premium content highlighting

---

### 🔄 **Refresh TMDB** (Blue Sync Button)
**Fungsi**: Update metadata dari TMDB (The Movie Database) API
- Fetches latest title, description, release date
- Updates poster & backdrop images
- Refreshes rating/vote average
- **Shows progress modal** dengan real-time tracking

**Use Case**:
- Updating outdated information
- Fixing incorrect metadata
- Getting latest posters/images
- Syncing dengan TMDB updates

**Note**: 
- Requires valid TMDB ID
- May take time untuk multiple items
- Shows progress: Total, Success, Failed
- Displays errors jika TMDB data not found

---

### 🗑️ **Delete** (Red Button)
**Fungsi**: **PERMANENT DELETE** items dari database
- ⚠️ **TIDAK BISA DI-UNDO!**
- Deletes item + all related data:
  - Movie sources
  - Views/analytics
  - Seasons & episodes (untuk series)
- **Double confirmation required**:
  1. Confirm dialog
  2. Type "DELETE" untuk confirm

**Use Case**:
- Removing duplicate entries
- Deleting copyright-violated content
- Cleaning up test data
- Permanent removal (not archive!)

**⚠️ WARNING**: This is DESTRUCTIVE! Use with extreme caution!

---

### ❌ **Clear** (Gray Button)
**Fungsi**: Clear selection
- Deselect semua items
- Hide bulk action bar
- Clear localStorage selection

**Use Case**:
- Starting fresh selection
- Accidentally selected wrong items
- Quick way to deselect all

---

## 🎬 Usage Workflow

### Example 1: Publishing Draft Movies
```
1. Go to Manage Movies
2. Filter by Status: "Draft"
3. Click "Select All" checkbox
4. Click "Publish" button
5. Confirm action
6. Wait for success toast
7. Page reloads - all items now Published
```

### Example 2: Featuring Trending Content
```
1. Go to Manage Movies/Series
2. Select 5-10 popular items (individual checkboxes)
3. Click "Feature" button
4. Confirm action
5. Items now marked as featured
6. They appear in Featured sections on homepage
```

### Example 3: Refreshing TMDB Data
```
1. Select items with outdated info
2. Click "Refresh TMDB" button
3. Confirm action
4. Progress modal shows:
   - Progress bar (0-100%)
   - Total/Success/Failed counts
   - Real-time updates
5. Click "Close & Reload" when complete
6. Updated metadata now visible
```

### Example 4: Archiving Old Content
```
1. Filter by Year: 2010 or older
2. Select multiple old movies
3. Click "Archive" button
4. Confirm action
5. Items archived - no longer public
6. Still accessible in admin for future restoration
```

---

## 🔒 Security Features

1. **CSRF Protection**: All actions protected dengan CSRF token
2. **Input Validation**: Server-side validation untuk all requests
3. **Authorization**: Admin-only access
4. **Rate Limiting**: 60 requests per minute
5. **SQL Injection Prevention**: Query builder dengan parameterized queries
6. **XSS Prevention**: HTML escaping on all outputs
7. **Audit Trail**: All bulk actions logged

---

## 📊 Progress Tracking

**For TMDB Refresh:**
- Real-time progress modal
- Visual progress bar
- Success/Failed counts
- Error list dengan details
- Auto-reload setelah complete

**For Other Actions:**
- Loading toast notification
- Success/error toast after completion
- Auto page reload untuk reflect changes

---

## 💡 Tips & Best Practices

1. **Test on Small Batch First**: Before bulk operating 100+ items, test dengan 5-10 items first
2. **Use Filters**: Combine dengan search/filter untuk targeted selections
3. **Archive Before Delete**: Always archive first, delete nanti jika sure
4. **Feature Strategically**: Don't feature too many items - loses impact
5. **Regular TMDB Refresh**: Refresh metadata monthly untuk accuracy
6. **Check Console Logs**: Jika ada issues, check browser console untuk debugging

---

## 🐛 Troubleshooting

### Issue: Selection not persisting
**Solution**: Clear browser localStorage dan refresh

### Issue: Action bar not showing
**Solution**: Check console logs, ensure JavaScript loaded

### Issue: Bulk action failed
**Solution**: 
- Check error message di toast
- Check console untuk detailed errors
- Try smaller batch size
- Check server logs

### Issue: TMDB refresh errors
**Solution**:
- Ensure items have valid TMDB ID
- Check TMDB API is accessible
- Some old content may not exist in TMDB

---

## 📞 Support

Jika ada issues atau questions:
1. Check console logs (F12)
2. Check Laravel logs: `storage/logs/laravel.log`
3. Screenshot error + console logs
4. Contact admin support

---

**Last Updated**: October 12, 2025  
**Version**: 1.0.0
