# TODO_PROJECT.md

## Pham vi da ra soat

- Laravel 12 app, route chinh trong `routes/web.php`.
- Controllers trong `app/Http/Controllers`.
- Models va relationships trong `app/Models`.
- Services/middleware: `AiAnalyzer`, `AdminProtectionService`, `EnsureRole`.
- Migrations, seeder va SQL dump trong `database/`.
- Blade views trong `resources/views`.
- Config co lien quan: auth, database, composer, package, tests.

Ghi chu: buoc nay chi phan tich va lap TODO. Khong sua code, khong doi ten bang/model/controller/route, khong thay doi cau truc database.

## 1. Chuc nang da co

- Dang nhap/dang xuat bang session va `Auth::attempt`, dung `username`, `password_hash`, `is_active`.
- Phan quyen route theo role qua middleware `role`: admin, staff, teacher, homeroom, student, parent.
- Dashboard theo role:
  - Admin/staff xem thong ke tong quan.
  - Giao vien xem phan cong giang day.
  - GVCN xem lop chu nhiem.
  - Hoc sinh xem diem va hanh kiem.
- Quan ly danh muc/hoc vu:
  - Nam hoc.
  - Hoc ky.
  - Lop hoc.
  - Mon hoc.
  - Giao vien.
  - Hoc sinh.
  - Phu huynh va lien ket hoc sinh.
  - Phan cong giang day.
- Quan ly khoa/mo nhap diem theo lop, mon, hoc ky qua `grade_windows`.
- Nhap diem theo lop/mon/hoc ky, tu tinh trung binh theo nhom he so 1/2/3.
- Nhap hanh kiem va nhan xet cho hoc sinh theo lop/hoc ky.
- Xem va quan ly thoi khoa bieu:
  - Xem theo lop/hoc ky.
  - Giao vien xem lich ca nhan.
  - Admin/staff quan ly o tiet theo thu/tiet.
- Tin nhan noi bo:
  - Hop thu den.
  - Da gui.
  - Tao tin nhan.
  - Xem chi tiet va danh dau da doc.
- AI noi bo theo rule:
  - Phan tich lop/hoc ky.
  - Tao nhan xet hoc sinh.
  - Tao canh bao rui ro.
  - Loc canh bao/bao cao theo role.
- Bao cao tong ket lop:
  - Diem trung binh.
  - Xep loai hoc luc.
  - Hanh kiem.
  - Thong ke so luong theo muc xep loai.
- Giao dien Bootstrap 5 voi sidebar, topbar, card/table/form co ban.

## 2. Chuc nang chua hoan thien

- Migration chua dong bo voi database SQL hien co:
  - Models dung UUID `varchar(50)`, nhung migration chinh van tao `id()` so nguyen.
  - Migration users tao `password`, `name`, `email`, nhung code thuc te dung `password_hash`, `is_active`, `parent_id`.
  - Migration thieu cac bang dang duoc code su dung: `parents`, `parent_student`, `messages`, `timetables`, `timetable_entries`, `ai_alerts`, `ai_reports`.
  - SQL dump co `student_transfers`, nhung code chua co model/controller/view cho chuc nang chuyen lop.
- Seeder chua an toan neu chay tren migration hien tai vi dang ghi cac cot khong co trong migration.
- Factory va test van la mac dinh Laravel, chua phu hop schema `users` hien tai.
- Quan ly tai khoan chua co man hinh rieng cho admin/staff:
  - Chua tao/sua/xoa user doc lap.
  - Chua doi role/is_active tap trung.
  - Chua reset mat khau theo quyen quan tri ngoai cac form giao vien/hoc sinh/phu huynh.
- Profile ho tro nhieu role, nhung mot so luong cap nhat chua chat che va co loi validation bang.
- Diem:
  - Chua co lich su thay doi diem.
  - Khi nhap diem khong hop le, he thong bo qua gia tri thay vi bao loi ro rang.
  - Chua co trang xem diem chi tiet theo tung cot cho hoc sinh/phu huynh.
- Hanh kiem:
  - Chua validate chat gia tri `conduct_level` trong request luu.
  - Chua co lich su hoac nguoi cap nhat.
- Thoi khoa bieu:
  - Chua kiem tra xung dot giao vien cung tiet o nhieu lop.
  - Chua kiem tra xung dot phong hoc.
  - Chua rang buoc giao vien co duoc phan cong dung mon/lop truoc khi xep lich.
  - Chua ho tro tuan cu the du `week_start`, `week_end` trong UI.
- Tin nhan:
  - Chua co tra loi/forward/xoa/luu nhap.
  - Danh sach nguoi nhan dang hien toan bo user active, chua gioi han theo ngu canh/phu trach.
- AI:
  - Dang la rule-based analyzer, chua ket noi dich vu AI thuc.
  - Ket qua nhan xet con khong dau/khong tu nhien o service.
  - Chua co man hinh xem chi tiet tung report/canh bao hoac danh dau da xu ly.
- Bao cao:
  - Chua co export Excel/PDF.
  - Chua co loc theo nam hoc/mon/nhom doi tuong nang cao.
  - Chua co bieu do.
- Tim kiem, loc, phan trang gan nhu chua co o cac danh sach lon.

## 3. Chuc nang con thieu

- Quan ly user/role tap trung cho admin/staff.
- Quan ly nhan vien `staff` rieng neu role nay tiep tuc duoc su dung.
- Quan ly chuyen lop/chuyen truong cho bang `student_transfers` da co trong SQL dump.
- Import/export danh sach hoc sinh, giao vien, diem.
- Lich su/audit log cho cac thao tac quan trong: diem, hanh kiem, user, phan cong, khoa diem.
- Trang chi tiet hoc sinh/giao vien/phu huynh thay vi chi co index/edit.
- Trang xem diem/hoc tap danh rieng cho phu huynh theo tung con.
- Phan quyen chi tiet hon cho teacher/homeroom/staff theo lop, mon, nam hoc.
- Quan ly nam hoc/hoc ky hien hanh chi co 1 active, tranh nhieu nam hoc active cung luc.
- Backup/restore hoac huong dan import SQL chuan hoa.
- Test feature cho cac workflow chinh: login, CRUD, nhap diem, khoa diem, hanh kiem, timetable, tin nhan, AI.
- Error pages 403/404/500 than thien hon.

## 4. Loi tiem an

- Schema mismatch la rui ro lon nhat:
  - Neu chay `php artisan migrate --seed` tren database moi, kha nang cao se loi do migration khong khop model/seeder.
  - UUID trait `UsesUuid` khong phu hop cac migration dang dung `id()` auto-increment.
- `ProfileController` validate `class_id` bang `exists:school_classes,id`, nhung bang hien tai la `classes`.
- `AdminProtectionService::validateAdminChange()` co bieu thuc:
  - `isset($changes['is_active']) && $changes['is_active'] === 0 || $changes['is_active'] === false`
  - Co the doc `$changes['is_active']` khi key khong ton tai do uu tien toan tu.
- Nhieu chuoi tieng Viet trong view/controller/README/SQL dang bi mojibake, anh huong hien thi va thong bao loi.
- `UserFactory` van tao `name`, `email`, `password`, `remember_token`, khong khop model `User` va SQL hien tai.
- `ExampleTest` ky vong `/` tra 200, nhung route `/` redirect den login; test co the khong dung muc tieu thuc te.
- Xoa nam hoc/lop/mon/giao vien/hoc sinh dung cascade co the xoa nhieu du lieu lien quan ma UI chi confirm don gian.
- Mot so form update khong xu ly truong hop duplicate DB unique bang thong bao than thien.
- `ScoreController::parseScores()` silently drop diem ngoai 0..10 hoac khong phai so, co the lam nguoi dung tuong da luu.
- `ConductController::store()` khong validate enum `conduct_level`.
- `TeachingAssignmentController` khong bat loi duplicate unique khi tao phan cong trung.
- `SchoolClassController@index` goi `$class->students->count()` khi relation chua eager load `students`, co nguy co N+1 query.
- Dashboard thong ke tong quan hien cho moi role, co the lam lo so lieu toan truong cho role khong nen thay.
- `AiController::reports()` hien chi admin/student/parent xem report; teacher/homeroom khong xem duoc report lop minh du route AI run cho homeroom.
- `TimetableController::index()` voi student/parent tu chon class nhung neu khong chon semester thi chua tu dong chon hoc ky active.
- CDN Bootstrap/Google Fonts/Bootstrap Icons yeu cau internet; khi chay noi bo/offline UI co the mat style/icon/font.

## 5. UI/UX can cai thien

- Sua encoding tieng Viet ve UTF-8 dong nhat tren Blade, README, message trong controller/service va SQL seed data.
- Them responsive wrapper cho cac bang lon (`table-responsive`) de dung tot tren mobile.
- Them empty state cho danh sach rong va ket qua loc rong.
- Them search/filter/sort/pagination cho:
  - Hoc sinh.
  - Giao vien.
  - Phu huynh.
  - Lop.
  - Phan cong.
  - Tin nhan.
  - AI alerts/reports.
- Giam phu thuoc style inline trong `layouts/app.blade.php`, tach CSS dung chuan asset pipeline neu can.
- Sidebar tren mobile can co collapse/offcanvas; hien tai `position: sticky` + width co dinh de gay tran man hinh.
- Form can hien loi theo tung field dong nhat, thay vi chi gom tat ca loi o flash.
- Nut thao tac nen thong nhat icon/text, them loading/disabled state khi submit cac form lon nhu nhap diem, TKB.
- Man hinh nhap diem can co validate truc quan, highlight o sai, va huong dan dinh dang ngan gon.
- Man hinh timetable can co grid ro hon, mau theo mon/giao vien, canh bao xung dot.
- Dashboard nen co noi dung rieng cho parent va staff; hien tai parent gan nhu chi thay thong ke chung va menu.
- Bao cao nen co tong quan truc quan hon: card thong ke, chart nho, nut export.

## Backlog uu tien de lam tiep

### P0 - On dinh nen tang va tranh loi chay lai du an

- [ ] Doi chieu database dang dung voi `database/school_manager.sql`, xac nhan schema chinh thuc.
- [ ] Dong bo migrations voi schema hien tai ma khong doi ten bang/model/controller/route dang dung.
- [ ] Dong bo `DatabaseSeeder` voi schema chinh thuc.
- [ ] Cap nhat `UserFactory` theo `username`, `password_hash`, `role`, `is_active`.
- [ ] Sua validation `exists:school_classes,id` thanh bang dang dung `classes`.
- [ ] Sua dieu kien trong `AdminProtectionService::validateAdminChange()` de khong doc key khong ton tai.
- [ ] Kiem tra lai `php artisan migrate --seed` tren database test rieng truoc khi dung cho database that.

### P1 - Sua loi nghiep vu va bao ve du lieu

- [ ] Them validation chat cho diem, hanh kiem, status hoc sinh, role user.
- [ ] Bao loi khi diem nhap sai thay vi bo qua am tham.
- [ ] Bat duplicate teaching assignment va grade window bang thong bao than thien.
- [ ] Chan thao tac xoa nguy hiem hoac canh bao so luong du lieu se bi xoa cascade.
- [ ] Kiem tra va gioi han dashboard/statistics theo role.
- [ ] Cho homeroom/teacher xem report AI cua lop/pham vi duoc phan quyen neu nghiep vu yeu cau.
- [ ] Them kiem tra xung dot thoi khoa bieu theo giao vien, phong, lop, tiet.

### P2 - Hoan thien chuc nang nguoi dung

- [ ] Xay man hinh quan ly user/role/is_active/reset password cho admin.
- [ ] Hoan thien staff role va menu/chuc nang rieng.
- [ ] Hoan thien chuc nang chuyen lop dua tren bang `student_transfers`.
- [ ] Them trang chi tiet hoc sinh/giao vien/phu huynh.
- [ ] Them view diem chi tiet cho hoc sinh/phu huynh.
- [ ] Them reply/delete cho tin nhan.
- [ ] Them export Excel/PDF cho bao cao va danh sach chinh.
- [ ] Them import Excel cho hoc sinh/giao vien/diem neu can.

### P3 - Nang cap UI/UX

- [ ] Sua toan bo mojibake tieng Viet ve UTF-8.
- [ ] Them `table-responsive`, empty state, pagination, search/filter.
- [ ] Lam sidebar mobile bang offcanvas/collapse.
- [ ] Chuan hoa form validation theo tung field.
- [ ] Tach style lon khoi layout sang CSS rieng neu tiep tuc dung Vite.
- [ ] Cai thien UI nhap diem va timetable cho de thao tac hang ngay.
- [ ] Bo hoac localize CDN assets neu moi truong truong hoc chay offline/no internet.

### P4 - Test va van hanh

- [ ] Viet feature tests cho login/logout va role middleware.
- [ ] Viet tests cho CRUD hoc vu chinh.
- [ ] Viet tests cho nhap diem, khoa diem, hanh kiem.
- [ ] Viet tests cho tin nhan va AI analyzer.
- [ ] Them huong dan setup ro rang: chon migrate/seed hay import SQL, khong de hai cach mau thuan.
- [ ] Them checklist backup database truoc cac thay doi schema trong tuong lai.
