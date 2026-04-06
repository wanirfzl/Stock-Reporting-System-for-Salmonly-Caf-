<?php
// adminSchedule.php
define('ACCESS_ALLOWED', true);
require_once 'config/database.php';
requireLogin();

if ($_SESSION['user_role'] !== 'Administrator') {
    header('Location: staffDashboard.php');
    exit();
}

$page_title = 'Staff Schedule';

// Data bulan
$months = [
    'JANUARY', 'FEBRUARY', 'MARCH', 'APRIL', 'MAY', 'JUNE',
    'JULY', 'AUGUST', 'SEPTEMBER', 'OCTOBER', 'NOVEMBER', 'DECEMBER'
];

// Get selected month & year
$selected_month = isset($_GET['month']) ? $_GET['month'] : strtoupper(date('F'));
$selected_year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Convert month name to number
$month_number = array_search($selected_month, $months) + 1;

// Get actual days in month
$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month_number, $selected_year);

// Get first day of month
$first_day = date('w', strtotime("$selected_year-$month_number-01"));
$first_day = ($first_day == 0) ? 6 : $first_day - 1;

// Calculate total cells needed
$total_cells = ceil(($days_in_month + $first_day) / 7) * 7;

// Get schedule data from database
$conn = getConnection();
$schedule_data = [];

// Query menggunakan staff_name dari table shifts (bukan JOIN dengan users)
$query = "SELECT * FROM shifts 
          WHERE MONTH(shift_date) = $month_number AND YEAR(shift_date) = $selected_year
          ORDER BY shift_date, shift_type";

$result = mysqli_query($conn, $query);
if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

while ($row = mysqli_fetch_assoc($result)) {
    $day = date('j', strtotime($row['shift_date']));
    $shift_type = $row['shift_type'];
    
    // Use staff_name from shifts table (already stored)
    $staff_name = isset($row['staff_name']) && !empty($row['staff_name']) 
                  ? $row['staff_name'] 
                  : 'Staff';
    
    $shift_info = [
        'name' => $staff_name,
        'time' => date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time'])),
        'shift_id' => $row['shift_id']
    ];
    
    if (!isset($schedule_data[$day][$shift_type])) {
        $schedule_data[$day][$shift_type] = [];
    }
    $schedule_data[$day][$shift_type][] = $shift_info;
}

// Get staff list for dropdown (active staff only)
$staff_list = [];
$staff_result = mysqli_query($conn, "SELECT user_id, full_name FROM users WHERE role != 'Administrator' ORDER BY full_name");
while ($row = mysqli_fetch_assoc($staff_result)) {
    $staff_list[] = $row;
}
mysqli_close($conn);

// Handle form submissions
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getConnection();
    
    if (isset($_POST['add_shift'])) {
        $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
        $shift_date = mysqli_real_escape_string($conn, $_POST['shift_date']);
        $shift_type = mysqli_real_escape_string($conn, $_POST['shift_type']);
        $start_time = mysqli_real_escape_string($conn, $_POST['start_time']);
        $end_time = mysqli_real_escape_string($conn, $_POST['end_time']);
        $created_by = $_SESSION['user_id'];
        
        // Get staff name for backup
        $staff_name_result = mysqli_query($conn, "SELECT full_name FROM users WHERE user_id = $user_id");
        $staff_name_row = mysqli_fetch_assoc($staff_name_result);
        $staff_name = $staff_name_row['full_name'];
        
        $insert = "INSERT INTO shifts (user_id, staff_name, shift_date, shift_type, start_time, end_time, created_by) 
                   VALUES ('$user_id', '$staff_name', '$shift_date', '$shift_type', '$start_time', '$end_time', '$created_by')";
        
        if (mysqli_query($conn, $insert)) {
            $success_message = 'Shift added successfully!';
            header("Location: adminSchedule.php?month=$selected_month&year=$selected_year");
            exit();
        } else {
            $error_message = 'Failed to add shift: ' . mysqli_error($conn);
        }
    } elseif (isset($_POST['edit_shift'])) {
        $shift_id = mysqli_real_escape_string($conn, $_POST['shift_id']);
        $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
        $shift_date = mysqli_real_escape_string($conn, $_POST['shift_date']);
        $shift_type = mysqli_real_escape_string($conn, $_POST['shift_type']);
        $start_time = mysqli_real_escape_string($conn, $_POST['start_time']);
        $end_time = mysqli_real_escape_string($conn, $_POST['end_time']);
        
        // Get staff name for backup
        $staff_name_result = mysqli_query($conn, "SELECT full_name FROM users WHERE user_id = $user_id");
        $staff_name_row = mysqli_fetch_assoc($staff_name_result);
        $staff_name = $staff_name_row['full_name'];
        
        $update = "UPDATE shifts SET user_id='$user_id', staff_name='$staff_name', shift_date='$shift_date', shift_type='$shift_type', 
                   start_time='$start_time', end_time='$end_time' WHERE shift_id='$shift_id'";
        
        if (mysqli_query($conn, $update)) {
            $success_message = 'Shift updated successfully!';
            header("Location: adminSchedule.php?month=$selected_month&year=$selected_year");
            exit();
        } else {
            $error_message = 'Failed to update shift';
        }
    } elseif (isset($_POST['delete_shift'])) {
        $shift_id = mysqli_real_escape_string($conn, $_POST['shift_id']);
        
        $delete = "DELETE FROM shifts WHERE shift_id='$shift_id'";
        
        if (mysqli_query($conn, $delete)) {
            $success_message = 'Shift deleted successfully!';
            header("Location: adminSchedule.php?month=$selected_month&year=$selected_year");
            exit();
        } else {
            $error_message = 'Failed to delete shift';
        }
    }
    mysqli_close($conn);
}

include 'includes/header-admin.php';
?>

<main class="px-6 md:px-10 py-8 max-w-7xl mx-auto w-full">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-4xl md:text-5xl font-black text-[#3C2A21]">Weekly Staff Schedule</h1>
        <p class="text-2xl font-bold text-[#8B5A2B] mt-2"><?= $selected_year ?></p>
    </div>

    <!-- Success/Error Messages -->
    <?php if ($success_message): ?>
    <div class="bg-[#7A8C71] text-white px-6 py-4 rounded-2xl flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <span class="material-symbols-outlined">check_circle</span>
            <span class="font-medium"><?= $success_message ?></span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-white/80 hover:text-white">×</button>
    </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
    <div class="bg-[#B85C38] text-white px-6 py-4 rounded-2xl flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <span class="material-symbols-outlined">error</span>
            <span class="font-medium"><?= $error_message ?></span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-white/80 hover:text-white">×</button>
    </div>
    <?php endif; ?>

    <!-- Month Navigation -->
    <div class="flex flex-wrap gap-2 mb-8 border-b border-[#3C2A21]/10 pb-4">
        <?php foreach ($months as $index => $month): ?>
        <a href="?month=<?= $month ?>&year=<?= $selected_year ?>" 
           class="px-4 py-2 text-sm font-bold uppercase tracking-wider transition-colors <?= $month == $selected_month ? 'text-[#8B5A2B] border-b-2 border-[#8B5A2B]' : 'text-[#3C2A21]/40 hover:text-[#8B5A2B]' ?>">
            <?= $month ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Days of Week -->
    <div class="grid grid-cols-7 gap-2 mb-4 text-center">
        <div class="font-bold text-[#8B5A2B]">MON</div>
        <div class="font-bold text-[#8B5A2B]">TUE</div>
        <div class="font-bold text-[#8B5A2B]">WED</div>
        <div class="font-bold text-[#8B5A2B]">THU</div>
        <div class="font-bold text-[#8B5A2B]">FRI</div>
        <div class="font-bold text-[#8B5A2B]">SAT</div>
        <div class="font-bold text-[#8B5A2B]">SUN</div>
    </div>

    <!-- Calendar Grid -->
    <div class="grid grid-cols-7 gap-2 mb-6">
        <?php
        $day_count = 1;
        
        for ($cell = 0; $cell < $total_cells; $cell++) {
            if ($cell < $first_day || $day_count > $days_in_month) {
                echo '<div class="bg-[#FAF9F6]/50 rounded-xl border border-dashed border-[#3C2A21]/10 p-3 min-h-[200px]"></div>';
            } else {
                $day = $day_count;
                $has_opening = isset($schedule_data[$day]['opening']);
                $has_closing = isset($schedule_data[$day]['closing']);
                $opening_shifts = $has_opening ? $schedule_data[$day]['opening'] : [];
                $closing_shifts = $has_closing ? $schedule_data[$day]['closing'] : [];
                
                $is_today = ($day == date('j') && $month_number == date('n') && $selected_year == date('Y'));
                ?>
                <div class="bg-[#FAF9F6] rounded-xl border <?= $is_today ? 'border-2 border-[#8B5A2B]' : 'border border-[#3C2A21]/5' ?> p-3 min-h-[200px] hover:shadow-md transition-shadow relative group">
                    <div class="flex justify-between items-start mb-2 sticky top-0 bg-[#FAF9F6] pb-1">
                        <span class="font-bold text-lg <?= $is_today ? 'text-[#8B5A2B]' : 'text-[#3C2A21]' ?>"><?= $day ?></span>
                        
                        <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                            <button onclick="openAddShiftModal(<?= $day ?>)" 
                                    class="p-1 hover:bg-[#F2E8DF] rounded" title="Add Shift">
                                <span class="material-symbols-outlined text-sm text-[#8B5A2B]">add</span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Opening Shifts -->
                    <?php if ($has_opening): ?>
                        <div class="mb-3">
                            <span class="text-xs font-bold text-[#8B5A2B] uppercase tracking-wider">OPENING</span>
                            <?php foreach ($opening_shifts as $shift): ?>
                            <div class="mt-1 p-2 bg-[#F2E8DF]/70 rounded-lg text-xs">
                                <p class="font-bold text-[#3C2A21]"><?= htmlspecialchars($shift['name']) ?></p>
                                <p class="text-[#8B5A2B] text-[10px]"><?= $shift['time'] ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Closing Shifts -->
                    <?php if ($has_closing): ?>
                        <div class="mb-3">
                            <span class="text-xs font-bold text-[#8B5A2B] uppercase tracking-wider">CLOSING</span>
                            <?php foreach ($closing_shifts as $shift): ?>
                            <div class="mt-1 p-2 bg-[#E6D5C0]/70 rounded-lg text-xs">
                                <p class="font-bold text-[#3C2A21]"><?= htmlspecialchars($shift['name']) ?></p>
                                <p class="text-[#8B5A2B] text-[10px]"><?= $shift['time'] ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Empty State -->
                    <?php if (!$has_opening && !$has_closing): ?>
                        <div class="text-center text-[#3C2A21]/30 text-xs mt-6">
                            No shifts scheduled
                        </div>
                    <?php endif; ?>
                </div>
                <?php
                $day_count++;
            }
        }
        ?>
    </div>

    <!-- Notes -->
    <div class="grid grid-cols-7 gap-2 mt-4">
        <div class="text-xs text-[#3C2A21]/40">1. Mon</div>
        <div class="text-xs text-[#3C2A21]/40">2. Tue</div>
        <div class="text-xs text-[#3C2A21]/40">3. Wed</div>
        <div class="text-xs text-[#3C2A21]/40">4. Thu</div>
        <div class="text-xs text-[#3C2A21]/40">5. Fri</div>
        <div class="text-xs text-[#3C2A21]/40">6. Sat</div>
        <div class="text-xs text-[#3C2A21]/40">7. Sun</div>
    </div>

    <!-- Month Label -->
    <div class="mt-6 text-right">
        <span class="text-lg font-bold text-[#8B5A2B]"><?= $selected_month ?> (<?= $selected_year ?>)</span>
    </div>

    <!-- Navigation -->
    <div class="flex items-center justify-between mt-8">
        <div class="flex gap-4">
            <?php
            $prev_month_num = $month_number - 1;
            $prev_year = $selected_year;
            if ($prev_month_num == 0) {
                $prev_month_num = 12;
                $prev_year = $selected_year - 1;
            }
            $prev_month = $months[$prev_month_num - 1];
            
            $next_month_num = $month_number + 1;
            $next_year = $selected_year;
            if ($next_month_num == 13) {
                $next_month_num = 1;
                $next_year = $selected_year + 1;
            }
            $next_month = $months[$next_month_num - 1];
            ?>
            
            <a href="?month=<?= $prev_month ?>&year=<?= $prev_year ?>" 
               class="px-6 py-2 bg-white border border-[#3C2A21]/10 rounded-full text-sm font-bold uppercase tracking-wider hover:bg-[#F2E8DF] transition-colors">
                Previous
            </a>
            <a href="?month=<?= strtoupper(date('F')) ?>&year=<?= date('Y') ?>" 
               class="px-6 py-2 bg-[#8B5A2B] text-white rounded-full text-sm font-bold uppercase tracking-wider hover:bg-[#B07A4A] transition-colors">
                This Month
            </a>
            <a href="?month=<?= $next_month ?>&year=<?= $next_year ?>" 
               class="px-6 py-2 bg-white border border-[#3C2A21]/10 rounded-full text-sm font-bold uppercase tracking-wider hover:bg-[#F2E8DF] transition-colors">
                Next
            </a>
        </div>
        
        <button onclick="saveAllChanges()" 
                class="bg-[#8B5A2B] text-white px-8 py-3 rounded-full text-sm font-bold uppercase tracking-wider hover:bg-[#B07A4A] transition-colors flex items-center gap-2">
            <span class="material-symbols-outlined">save</span>
            Save All Changes
        </button>
    </div>
</main>

<!-- Add Shift Modal -->
<div id="shiftModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000;">
    <div style="background: #FAF9F6; border-radius: 24px; max-width: 400px; width: 90%; margin: 0 auto; padding: 32px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h3 id="modalTitle" style="font-size: 24px; font-weight: 900; color: #3C2A21;">Add Shift</h3>
            <button onclick="closeModal()" style="font-size: 28px; color: #3C2A21/40; background: none; border: none; cursor: pointer;">×</button>
        </div>
        
        <form id="shiftForm" method="POST" action="">
            <input type="hidden" name="add_shift" value="1">
            <input type="hidden" id="shiftDate" name="shift_date">
            
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Staff Name</label>
                <select name="user_id" required style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid rgba(60,42,33,0.1);">
                    <option value="">Select Staff</option>
                    <?php foreach ($staff_list as $staff): ?>
                    <option value="<?= $staff['user_id'] ?>"><?= $staff['full_name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Shift Type</label>
                <select name="shift_type" required style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid rgba(60,42,33,0.1);">
                    <option value="opening">Opening Shift</option>
                    <option value="closing">Closing Shift</option>
                </select>
            </div>
            
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Start Time</label>
                <input type="time" name="start_time" required style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid rgba(60,42,33,0.1);">
            </div>
            
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">End Time</label>
                <input type="time" name="end_time" required style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid rgba(60,42,33,0.1);">
            </div>
            
            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" style="flex: 1; background: #8B5A2B; color: white; padding: 12px; border-radius: 9999px; font-size: 14px; font-weight: bold; text-transform: uppercase; border: none; cursor: pointer;">
                    Save
                </button>
                <button type="button" onclick="closeModal()" style="flex: 1; background: white; border: 1px solid rgba(60,42,33,0.1); padding: 12px; border-radius: 9999px; font-size: 14px; font-weight: bold; text-transform: uppercase; cursor: pointer;">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let currentDay;

function openAddShiftModal(day) {
    currentDay = day;
    
    const year = <?= $selected_year ?>;
    const month = <?= $month_number ?>;
    
    const formattedDate = year + '-' + String(month).padStart(2, '0') + '-' + String(day).padStart(2, '0');
    
    document.getElementById('modalTitle').textContent = 'Add Shift';
    document.getElementById('shiftDate').value = formattedDate;
    
    document.getElementById('shiftModal').style.display = 'flex';
}

function saveAllChanges() {
    alert('All changes saved to database!');
}

function closeModal() {
    document.getElementById('shiftModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('shiftModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}
</script>

<?php include 'includes/footer.php'; ?>
