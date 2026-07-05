<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $selectedType = $request->query('type', 'all');
        $selectedStatus = $request->query('status', 'all');
        $readOnly = $this->isHistoricalReadOnly();

        $rooms = Room::query()
            ->when($selectedType !== 'all', fn ($query) => $query->where('type', $selectedType))
            ->when($selectedStatus !== 'all', fn ($query) => $query->where('status', $selectedStatus))
            ->withCount('timetableEntries')
            ->orderBy('name')
            ->get();

        return view('rooms.index', compact('rooms', 'selectedType', 'selectedStatus', 'readOnly'));
    }

    public function create()
    {
        if ($this->isHistoricalReadOnly()) {
            return redirect()->route('rooms.index')->withErrors([
                'room' => 'Đang xem dữ liệu lịch sử, không thể thêm phòng học.',
            ]);
        }

        return view('rooms.create');
    }

    public function store(Request $request)
    {
        $this->denyHistoricalWrite();

        $data = $this->validatedData($request);

        $room = Room::create($data);

        AuditLogger::log('room_created', Room::class, (string) $room->getKey(), 'Tạo phòng học ' . $room->name);

        return redirect()->route('rooms.index')->with('success', 'Đã thêm phòng học.');
    }

    public function edit(Room $room)
    {
        if ($this->isHistoricalReadOnly()) {
            return redirect()->route('rooms.index')->withErrors([
                'room' => 'Đang xem dữ liệu lịch sử, không thể chỉnh sửa phòng học.',
            ]);
        }

        return view('rooms.edit', [
            'room' => $room,
            'isUsed' => $room->isUsed(),
        ]);
    }

    public function update(Request $request, Room $room)
    {
        $this->denyHistoricalWrite();

        $data = $this->validatedData($request, $room);
        $oldStatus = $room->status;

        if ($room->isUsed() && $data['name'] !== $room->name) {
            throw ValidationException::withMessages([
                'name' => 'Phòng học đã phát sinh thời khóa biểu, không thể sửa tên phòng.',
            ]);
        }

        $room->update($data);

        AuditLogger::log('room_updated', Room::class, (string) $room->getKey(), 'Sửa phòng học ' . $room->name);

        if ($oldStatus !== $room->status) {
            AuditLogger::log('room_status_changed', Room::class, (string) $room->getKey(), 'Đổi trạng thái phòng học ' . $room->name . ' sang ' . $room->statusLabel());
        }

        return redirect()->route('rooms.index')->with('success', 'Đã cập nhật phòng học.');
    }

    public function destroy(Room $room)
    {
        $this->denyHistoricalWrite();

        if (! $room->canDelete()) {
            return back()->withErrors([
                'room' => 'Không thể xóa phòng học vì đã phát sinh thời khóa biểu. Hãy chuyển trạng thái sang Ngưng sử dụng nếu không còn dùng.',
            ]);
        }

        $roomName = $room->name;
        $roomId = (string) $room->getKey();

        DB::transaction(function () use ($room, $roomName, $roomId) {
            $room->delete();
            AuditLogger::log('room_deleted', Room::class, $roomId, 'Xóa phòng học ' . $roomName);
        });

        return redirect()->route('rooms.index')->with('success', 'Đã xóa phòng học.');
    }

    private function validatedData(Request $request, ?Room $room = null): array
    {
        $request->merge([
            'name' => trim((string) $request->input('name')),
            'custom_type' => trim((string) $request->input('custom_type')),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('rooms', 'name')->ignore($room?->getKey())],
            'type' => ['required', Rule::in(array_keys(Room::TYPES))],
            'custom_type' => ['nullable', 'string', 'max:255', Rule::requiredIf($request->input('type') === Room::TYPE_OTHER)],
            'capacity' => ['required', 'integer', 'min:1', 'max:100'],
            'status' => ['required', Rule::in(array_keys(Room::STATUSES))],
            'note' => ['nullable', 'string', 'max:2000'],
        ], [
            'name.unique' => 'Tên phòng đã tồn tại.',
            'custom_type.required' => 'Vui lòng nhập loại phòng khi chọn Khác.',
            'capacity.min' => 'Sức chứa tối thiểu là 1.',
            'capacity.max' => 'Sức chứa tối đa là 100.',
        ]);

        if ($data['type'] !== Room::TYPE_OTHER) {
            $data['custom_type'] = null;
        }

        return $data;
    }

    private function denyHistoricalWrite(): void
    {
        if ($this->isHistoricalReadOnly()) {
            throw ValidationException::withMessages([
                'history_readonly' => 'Đang xem dữ liệu lịch sử, không thể thay đổi phòng học.',
            ]);
        }
    }
}
